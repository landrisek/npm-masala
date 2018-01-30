package masala

import ("encoding/json"
	"fmt"
	"io/ioutil"
	"net/http"
	"strings")

type Grid struct {
	payload Payload
}

type Payload struct {
	Filters map[string]string
	Message string
	Offset int32
	Sort []string
	Status string
	Stop int32
}

func (grid Grid) done() {
	fmt.Print("done")
}

func (grid Grid) getRequest() string {
	request := "filters"
	for key, filter := range grid.payload.Filters {
		request += "[" + key + "]=" + filter
	}
	if "filters" == request {
		request += "{}"
	}
	request += "&sort{}&offset=0"
	return request
}

func (grid Grid) Inject(payload Payload) Grid {
	grid.payload = payload
	return grid
}

func (grid Grid) Prepare() {
	grid.setState("prepare").run()
}

func (grid Grid) run() {
	if grid.payload.Stop > grid.payload.Offset {
		fmt.Print(grid.payload)
		/* axios.post(this.state[BUTTONS].run, payload).then(response => {
					var buttons = this.state[BUTTONS]
					buttons[key].width = payload.offset / (payload.stop / 100)
					var state = []
					state[BUTTONS] = buttons
					if('service' == response.data.status && 'object' == typeof(response.data.row) && SIZE > payload.offset) {
						state[ROWS] = this.state[ROWS]
						for(var row in response.data.row) {
							state[ROWS][parseInt(row) + parseInt(payload.offset)] = response.data.row[row]
						}
					}
					this.setState(state)
					this.run(response.data, key)
				})
		*/
	} else {
		grid.done()
	}
}

func (grid Grid) setState(handler string) Grid {
	request, _ := http.NewRequest("POST",
		strings.Join([]string{"http://10.10.0.100/4camping.cz/lubo/sklad/cron/reorders?key=IJlJtMv3qh0caFpY&do=masala-", handler}, ""),
		strings.NewReader(grid.getRequest()))
	request.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	response, _ := http.DefaultClient.Do(request)
	payload, _ := ioutil.ReadAll(response.Body)
	defer response.Body.Close()
	json.Unmarshal(payload, &grid.payload)
	return grid
}