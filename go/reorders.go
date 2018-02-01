package main

import ("masala")

func main() {
	input := masala.Payload{}
	input.Filters = map[string]interface{}{"producers_id":[]string{"_2"},"reorder":"clicked"}
	input.Sort = []string{}
	input.Status = "service"
	masala.Grid{}.Inject(input, "http://10.10.0.100/4camping.cz/lubo/sklad/cron/reorders?key=IJlJtMv3qh0caFpY").Prepare()
}