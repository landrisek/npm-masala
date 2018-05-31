package main

import (
	"encoding/json"
	"io/ioutil"
	"github.com/landrisek/mytago"
	"fmt"
	"net/http"
	"path/filepath"
	"regexp"
	"strconv"
	"time"
	"os"
	"log"
)

var (
	base, _ = filepath.Abs("./")
	builder = mytago.Builder{}.Inject(base + "/config.yml")
)

func main() {
	file, err := os.Create("../test.csv")
	file.Write([]byte("test" + "\n"))
	if err != nil {
		log.Fatal(err)
	}
	defer file.Close()
	/** date */
	from := time.Now()
	from = time.Date(2013, 07, 16, 0, 0, 0, 0, time.UTC)
	month := strconv.Itoa(int(from.Month()))
	if 1 == len(month) {
		month = "0" + month
	}
	/** row */
	callback := func(data map[string]string) string {
		status := "pending"
		statuses := map[string]string{"7":"storno","16":"storno","17":"storno","100":"storno","3":"paid","4":"paid","8":"paid"}
		if state, exist := statuses[data["status_id"]]; exist  {
			status = state
		}
		row := data["id"] + builder.Config.Separator
		row += regexp.MustCompile("([A-Z])").ReplaceAllString(data["date"], " ") + builder.Config.Separator
		row += data["email"] + builder.Config.Separator
		row += data["price_without_vat_czk"] + builder.Config.Separator
		row += getMargin(data["id"]) + builder.Config.Separator
		row += status + builder.Config.Separator
		row += data["shipping_methods_name_cs"] + builder.Config.Separator
		row += data["payment_terms_name_cs"] + builder.Config.Separator
		row += getVoucher(data["vouchers_id"]) + builder.Config.Separator
		row += getShoppings(data["email"]) + builder.Config.Separator
		row += data["zipcode"] + builder.Config.Separator
		row += isSubscribed(data["email"]) + builder.Config.Separator
		return row
	}
	/** implementation */
	builder.Table(builder.Config.Tables.Orders).Select(map[string]string{"id":"id","date":"date","email":"email",
		"payment_terms_name_cs":"payment_terms_name_cs","price_without_vat_czk":"price_without_vat_czk",
		"shipping_methods_name_cs":"shipping_methods_name_cs","status_id":"status_id","vouchers_id":"vouchers_id","zipcode":"zipcode"}).Header(
		"order;date;customer;revenue;margin;status;delivery;payment;discount;previous_shoppings;zipcode;subscribed").Where(
		"date >=", strconv.Itoa(from.Year()) + "-" + month + "-" + strconv.Itoa(from.Day()) + " 00:00:00").Where(
		"date <=", strconv.Itoa(from.Year()) + "-" + month + "-" + strconv.Itoa(from.Day()) + " 24:00:00").Order(
		"id ASC").Limit(10).Prepare().Write(callback)
	fmt.Print("csv task done")
}

func getMargin(id string) string {
	tables := builder.Config.Tables
	rows := builder.Table(tables.Products).Select(map[string]string{"products_id":tables.Products + ".products_id","close":tables.Transactions +
		".close","margin":tables.Products + ".price_without_vat_czk * " + tables.Products + ".quantity - " + tables.Transactions +
		".price_purchase_czk * " + tables.Products + ".quantity","transactions_id":tables.Transactions + ".id"}).LeftJoin(tables.Orders +
		" ON " + tables.Orders + ".id = " + tables.Products + ".orders_id").LeftJoin(tables.Transactions + " ON (" + tables.Transactions +
		".products_id = " + tables.Products + ".products_id AND DATE_FORMAT(" + tables.Transactions + ".date, 'Y-m-d') = DATE_FORMAT(" +
		tables.Orders + ".date, 'Y-m-d'))").Where(tables.Products + ".orders_id =", id).Where(tables.Products + ".products_id IS NOT NULL",
		"").Where(tables.Transactions + ".id IS NOT NULL", "").Where(tables.Transactions + ".close =", "1").Group(tables.Products +
		".products_id, " + tables.Products + ".orders_id").Limit(10).Prepare().Fetch()
	total := 0.0
	for _, row := range rows {
		add, _ := strconv.ParseFloat(row["margin"], 64)
		total += add
	}
	return strconv.FormatFloat(total, 'f', -1, 64)
}

func getVoucher(id string) string {
	rows := builder.Table(builder.Config.Tables.Vouchers).Select(map[string]string{"id":"id","description":"description","type":"type",
		"value":"value"}).Where("id", id).Limit(1).Prepare().Fetch()
	for _, row := range rows {
		return row["description"] + " " + row["type"] + " " + row["value"]
	}
	return ""
}

func getShoppings(email string) string {
	return builder.Table(builder.Config.Tables.Orders).Select(map[string]string{"sum":"COUNT(email)"}).Where(
		"email =", email).Limit(1).Prepare().Fetch()[0]["sum"]
}

type Subscription struct {
	Key struct {
		Id int
		Key string
		Title string
		Description bool
		Parent int
		Roles bool
		Subscribe int
	}
}


func isSubscribed(email string) string {
	response, err := http.Get(builder.Config.Api +  email)
	if err != nil {
		panic(err.Error())
	}
	body, err := ioutil.ReadAll(response.Body)
	if err != nil {
		panic(err.Error())
	}
	var data map[string]map[string]interface{}
	json.Unmarshal(body, &data)
	subscribed := "0"
	if _, exist := data["vs4c"]; exist {
		subscriptions := data["vs4c"]["subscriptions"].(map[string]interface{})
		subscription := subscriptions["1"].(map[string]interface{})
		subscribed = strconv.FormatFloat(subscription["subscribe"].(float64), 'f', -1, 64)
	}
	return subscribed
}