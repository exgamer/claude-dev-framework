package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-core/pkg/exception"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators"
	"github.com/gin-gonic/gin"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
)

func NewHandler(tariffService *tariffdomain.Service) *Handler {
	return &Handler{
		tariffService: tariffService,
	}
}

// Handler Тарифы для машинного вызова партнёра; права администратора и скоуп парковок к нему не применяются
type Handler struct {
	tariffService *tariffdomain.Service
}

// @Summary		Тариф по ID для партнёра
// @Description	Машинный вызов: ключ API и HMAC-подпись запроса, повтор подписи — 401. Партнёр видит тариф любой парковки
// @Tags			integration-tariff
// @Produce		json
// @Param			id	path	int	true	"ID тарифа"
// @Security		PartnerAPIKey && PartnerSignature
// @Success		200	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		413	{object}	structures.BadRequestErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/integration/v1/billing/tariffs/{id} [get]
func (h *Handler) View() gin.HandlerFunc {
	return func(c *gin.Context) {
		id, err := validators.GetIntQueryParam(c, "id")
		if err != nil {
			response.ErrorResponse(c, exception.NewValidationException(map[string]any{"id": "id должен быть положительным целым числом"}, false))

			return
		}

		// Машинный вызов без пользователя: скоупа по парковкам нет
		tariff, err := h.tariffService.GetByID(c.Request.Context(), uint(id), tariffdomain.ParkingScope{All: true})
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}
