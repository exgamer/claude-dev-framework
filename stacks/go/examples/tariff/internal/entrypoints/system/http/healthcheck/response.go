package healthcheck

import "git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/structures"

// statusData Состояние сервиса в ответе
type statusData struct {
	Status string `json:"status"`
}

type statusResponse struct {
	structures.Response[statusData]
}
