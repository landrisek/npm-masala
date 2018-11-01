package main

import ("fmt"
	"masala"
	"strings"
	"strconv")

type ReorderFacade struct {

}

var URL = "myurl"

func (facade ReorderFacade) Done(payload masala.Payload) {
	input := masala.Payload{}
	_, reorder := payload.Filters["reorder"]
	_, filters := payload.Filters["producers_id"]
	fmt.Print(payload, "\n")
	if false == reorder && true == filters {
		payload.Filters["reorder"] = "clicked"
		input.Filters = payload.Filters
		input.Sort = []string{}
		masala.Grid{}.Inject(input, facade, URL).Prepare()
	} else if _, exist := payload.Data["price_purchase_czk"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{URL, "default?id=", id}, "")
		masala.Grid{}.Inject(input, facade, url).Prepare()
	} else if _, exist := payload.Data["total"]; exist {
		id := strconv.Itoa(int(payload.Data["fc_reorders_id"].(float64)))
		url := strings.Join([]string{URL, "submit?id=", id}, "")
		masala.Grid{}.Inject(input, facade, url).Prepare()
	} else {
		fmt.Print("done\n")
	}
}

func main() {
	input := masala.Payload{}
	input.Filters = map[string]interface{}{"producers_id":[]string{"_133"}}
	input.Sort = []string{}
	var facade masala.IProcess = &ReorderFacade{}
	input.Status = "facade"
	masala.Grid{}.Inject(input, facade, URL).Prepare()
}

func (facade ReorderFacade) Run(payload masala.Payload) {

}

func (facade ReorderFacade) Prepare(payload masala.Payload) {

}
