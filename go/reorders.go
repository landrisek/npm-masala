package main

import ("masala")

func main() {
	input := masala.Payload{}
	input.Filters = map[string]string{"producers_id":"_155"}
	masala.Grid{}.Inject(input).Prepare()
}