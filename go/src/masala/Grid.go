package masala

import ("bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"strings")

type Grid struct {
	payload Payload
	service IProcess
	url string
}

type Payload struct {
	Filters map[string]interface{}
	Data map[string]interface{}
	Message string
	Offset int
	Row []interface{}
	Sort []string
	Status string
	Stop int
}

type IProcess interface {
	Done(payload Payload)
	Run(payload Payload)
	Prepare(payload Payload)
}

func (grid Grid) done() {
	grid.setState("done")
	grid.service.Done(grid.payload)
}

func (grid Grid) Inject(payload Payload, service IProcess, url string) Grid {
	grid.payload = payload
	grid.service = service
	grid.url = url
	return grid
}

func (grid Grid) Prepare() {
	grid.setState("prepare").run()
	grid.service.Prepare(grid.payload)
}

func (grid Grid) run() {
	if grid.payload.Stop > grid.payload.Offset {
		grid.setState("run").run()
		grid.service.Run(grid.payload)
	} else {
		grid.done()
	}
}

func (grid Grid) setState(handler string) Grid {
	state, _ := json.Marshal(grid.payload)
	response, _ := http.Post(strings.Join([]string{grid.url, "&do=masala-", handler}, ""), "applications/json", bytes.NewBuffer(state))
	defer response.Body.Close()
	payload, _ := ioutil.ReadAll(response.Body)
	if "run" == handler {
		fmt.Print(string(payload), "\n")
	}
	data := Payload{}
	json.Unmarshal(payload, &data)
	grid.payload = data
	return grid
}