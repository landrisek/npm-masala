package controllers

import ("bufio"
        "encoding/json"
        "facades"
        "fmt"
        "io"
        "html/template"
        "masala"
        "net/http"
        "os"
        "regexp"
        "repositories"
        "strconv"
        "strings"
        "time")

type MyController struct {
        builder masala.SqlBuilder
        id int
        limit int
        logFacade facades.LogFacade
        scheme string
        source string
        myRepository repositories.MyRepository
        translatorRepository repositories.TranslatorRepository
}

func (controller MyController) Inject() MyController {
        controller.builder = masala.SqlBuilder{}.Inject()
        controller.source = controller.builder.Config.Tables["source"]
        controller.limit = 20
        controller.logFacade = facades.LogFacade{}.Inject()
        controller.myRepository = repositories.MyRepository{}.Inject()
        controller.scheme = "http://"
        controller.translatorRepository = repositories.TranslatorRepository{}.Inject()
        return controller
}

func (controller MyController) Upload(response http.ResponseWriter, request *http.Request) {
        defer request.Body.Close()
        upload, error := os.Create("../../temp/import.csv")
        controller.logFacade.Error(facades.Log{error, "Upload failed"})
        defer upload.Close()
        io.Copy(upload, request.Body)
}

func (controller MyController) Uploaded(response http.ResponseWriter, request *http.Request) {
        payload := controller.builder.GetState(request)
        controller.id = controller.logFacade.Integer(strconv.Atoi(payload.Where["id"]))
        state, _ := json.Marshal(payload)
        file := template.Must(template.New("state.html").ParseFiles("../templates/state.html"))
        file.Execute(response, map[string]interface{}{"state":template.HTML(string(state))})
        queue := make(chan string)
        complete := make(chan bool)
        go func() {
                file, error := os.Open("../../temp/import.csv")
                controller.logFacade.Error(facades.Log{error, "Failed to open csv,"})
                defer file.Close()
                scanner := bufio.NewScanner(file)
                for scanner.Scan() {
                        queue <- scanner.Text()
                }
                close(queue)
        }()
        for i := 0; i < 10; i++ {
                go controller.read(queue, complete)
        }
        for i := 0; i < 10; i++ {
                <-complete
        }
}

func (controller MyController) Page(response http.ResponseWriter, request *http.Request) {
        paginator, error := json.Marshal(controller.builder.Table(controller.source).Page("", controller.limit, request))
        controller.logFacade.Error(facades.Log{error, "Json failed"})
        file := template.Must(template.New("page.html").ParseFiles("../templates/page.html"))
        file.Execute(response, map[string]interface{}{"paginator":template.HTML(string(paginator))})
}

func (controller MyController) Props(response http.ResponseWriter, request *http.Request) {
        if pusher, success := response.(http.Pusher); success {
        		error := pusher.Push("/app.js", nil)
                controller.logFacade.Error(facades.Log{error, "Failed to push"})
        }
        props := controller.builder.Props(request, controller.translatorRepository)
        props["count"] = map[string]string{"label":controller.translatorRepository.Translate("sum")}
        props["currency"] = map[string]string{"label":controller.translatorRepository.Translate("currency")}
        props["date"] = map[string]string{"label":controller.translatorRepository.Translate("date")}
        props["ean"] = map[string]string{"label":"ean"}
        props["edit"] = map[string]string{"label":controller.translatorRepository.Translate("edit")}
        props["price_purchase_sum"] = map[string]string{"label":controller.translatorRepository.Translate("sum of price")}
        props["products_name"] = map[string]string{"label":controller.translatorRepository.Translate("name of product")}
        props["products_variants_name"] = map[string]string{"label":controller.translatorRepository.Translate("products_variants_name")}
        props["remove"] = map[string]string{"label":controller.translatorRepository.Translate("remove")}
        props["upload"] = map[string]string{"label":controller.translatorRepository.Translate("import transfers"),
                                            "link":controller.scheme + request.Host + request.URL.Path + "/upload",
                                            "onSuccess":controller.scheme + request.Host + request.URL.Path + "/uploaded"}
        data, _ := json.Marshal(props)
        file := template.Must(template.New("props.html").ParseFiles("../templates/props.html"))
        file.Execute(response, map[string]interface{}{"props":template.HTML(string(data))})
}

func (controller MyController) read(queue chan string, complete chan bool) {
        i := 0
        for line := range queue {
                if i > 0 {
                        row := strings.Split(line, ",")
                        myRow := controller.myRepository.GetRow(row[0])
                        /** application logic **/
                }
                i++
        }
        complete <- true
}

func (controller MyController) State(response http.ResponseWriter, request *http.Request) {
        state, _ := json.Marshal(controller.builder.Table(controller.source).Select("count, currency, ean, SUM(count) * price_purchase_czk AS price_puchase_sum, products_name, products_variants_name").State(
                controller.source + ".id", controller.limit, request))
        file := template.Must(template.New("state.html").ParseFiles("../templates/state.html"))
        file.Execute(response, map[string]interface{}{"state":template.HTML(string(state))})
}