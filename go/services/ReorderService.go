package main

import ("fmt"
	"masala"
		"strings"
		"strconv")

type ReorderService struct {

}

func (service ReorderService) Done(payload masala.Payload) {
	input := masala.Payload{}
	if _, exist := payload.Data["price_purchase_czk"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{"http://10.10.0.100/4camping.cz/lubo/sklad/cron/reorder?key=IJlJtMv3qh0caFpY&id=", id}, "")
		masala.Grid{}.Inject(input, service, url).Prepare()
	} else if _, exist := payload.Data["total"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{"http://10.10.0.100/4camping.cz/lubo/sklad/cron/submit?key=IJlJtMv3qh0caFpY&id=", id}, "")
		masala.Grid{}.Inject(input, service, url).Prepare()
	} else {
		fmt.Print("done\n")
	}
}

func main() {
	input := masala.Payload{}
	input.Filters = map[string]interface{}{"producers_id":[]string{"_133"},"reorder":"clicked"}
	input.Sort = []string{}
	var service masala.IProcess = &ReorderService{}
	input.Status = "service"
	masala.Grid{}.Inject(input, service, "http://10.10.0.100/4camping.cz/lubo/sklad/cron/reorders?key=IJlJtMv3qh0caFpY").Prepare()
}

func (service ReorderService) Run(payload masala.Payload) {

}

func (service ReorderService) Prepare(payload masala.Payload) {

}
