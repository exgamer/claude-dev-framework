package main

import (
	"log"

	"example.com/parking-service/internal/console"
)

// main Консоль (миграции, повтор outbox по cron): одна команда из аргументов и выход, без HTTP и consumer-ов
func main() {
	appInstance, err := console.NewApp()
	if err != nil {
		log.Fatal(err)
	}

	err = appInstance.RunAll()
	appInstance.Close()

	if err != nil {
		log.Fatal(err)
	}
}
