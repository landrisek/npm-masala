package masala

import ("bytes"
	"encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"strings")

type Grid struct {
	payload Payload
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

func (grid Grid) done() {
	grid.setState("done")
	fmt.Print(grid.payload, "\n")
}

func (grid Grid) Inject(payload Payload, url string) Grid {
	grid.payload = payload
	grid.url = url
	return grid
}

func (grid Grid) Prepare() {
	grid.setState("prepare").run()
}

func (grid Grid) run() {
	if grid.payload.Stop > grid.payload.Offset {
		grid.setState("run").run()
	} else {
		grid.done()
	}
}

func (grid Grid) setState(handler string) Grid {
	state, _ := json.Marshal(grid.payload)
	response, _ := http.Post(strings.Join([]string{grid.url, "&do=masala-", handler}, ""), "applications/json", bytes.NewBuffer(state))
	defer response.Body.Close()
	payload, _ := ioutil.ReadAll(response.Body)
	data := Payload{}
	json.Unmarshal(payload, &data)
	fmt.Print(grid.payload.Offset, "\n")
	grid.payload = data
	return grid
}