package requestid

import (
	"context"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/constants"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/gin"
)

// Header Заголовок Request ID для исходящего запроса; вне HTTP-запроса (consumer, консоль, фон после ответа) — пустой набор
func Header(ctx context.Context) map[string]string {
	if info := gin.GetHttpInfoFromContext(ctx); info != nil && info.RequestId != "" {
		return map[string]string{constants.RequestIdHeaderName: info.RequestId}
	}

	return map[string]string{}
}
