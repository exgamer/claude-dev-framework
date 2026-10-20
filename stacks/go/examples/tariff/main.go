package main

import (
	"log"

	"example.com/parking-service/internal/app"
)

// @title			Parking Service (API)
// @version		1.0
// @description	Тарифы парковок: админ-API, API партнёра, события тарифов в RabbitMQ
// @host			0.0.0.0:8090
// @BasePath		/
// @securityDefinitions.apikey	BearerAuth
// @in							header
// @name						Authorization
// @description				JWT администратора: Authorization: Bearer <token>
// @securityDefinitions.apikey	PartnerAPIKey
// @in							header
// @name						Api-Key
// @description				Ключ партнёра
// @securityDefinitions.apikey	PartnerSignature
// @in							header
// @name						X-Signature
// @description				t=<unix ts>,n=<nonce>,v1=<hex HMAC-SHA256(secret, "{t}\n{n}\n{METHOD}\n{path?query}\n{body}")>
func main() {
	appInstance, err := app.NewApp()
	if err != nil {
		log.Fatal(err)
	}

	err = appInstance.RunAll()
	if err != nil {
		log.Fatal(err)
	}

	appInstance.WaitForShutdown()
}
