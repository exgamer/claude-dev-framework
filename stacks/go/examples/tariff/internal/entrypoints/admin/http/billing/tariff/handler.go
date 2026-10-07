package tariff

import (
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators"
	"github.com/gin-gonic/gin"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func NewHandler(
	tariffService *tariffdomain.Service,
	setDefaultTariffCommand *tariffdomain.SetDefaultTariffCommand,
	createTariffWorkflow *tariffworkflow.CreateTariffWorkflow,
) *Handler {
	return &Handler{
		tariffService:           tariffService,
		setDefaultTariffCommand: setDefaultTariffCommand,
		createTariffWorkflow:    createTariffWorkflow,
	}
}

type Handler struct {
	tariffService           *tariffdomain.Service
	setDefaultTariffCommand *tariffdomain.SetDefaultTariffCommand
	createTariffWorkflow    *tariffworkflow.CreateTariffWorkflow
}

// @Summary		Список тарифов
// @Tags			admin-tariff
// @Produce		json
// @Param			parking_id	query	int	false	"ID парковки"
// @Param			page		query	int	false	"Страница"
// @Param			per_page	query	int	false	"Записей на странице (до 100)"
// @Security		BearerAuth
// @Success		200	{object}	tariffListResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs [get]
func (h *Handler) Index() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := indexRequest{}
		if ve := validators.ValidateRequestQuery(c, &request); ve == false {
			return
		}

		result, err := h.tariffService.Paginated(c.Request.Context(), indexRequestToSearch(request))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, paginatedToData(result))
	}
}

// @Summary		Тариф по ID
// @Tags			admin-tariff
// @Produce		json
// @Param			id	path	int	true	"ID тарифа"
// @Security		BearerAuth
// @Success		200	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs/{id} [get]
func (h *Handler) View() gin.HandlerFunc {
	return func(c *gin.Context) {
		id, err := validators.GetIntQueryParam(c, "id")
		if err != nil {
			response.BadRequest(c, err, nil)

			return
		}

		tariff, err := h.tariffService.GetByID(c.Request.Context(), uint(id))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}

// @Summary		Создать тариф
// @Description	Тариф создаётся не дефолтным; дефолт назначается отдельным запросом
// @Tags			admin-tariff
// @Accept			json
// @Produce		json
// @Param			message	body	createRequest	true	"Тариф"
// @Security		BearerAuth
// @Success		201	{object}	tariffResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs [post]
func (h *Handler) Create() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := createRequest{}
		if ve := validators.ValidateRequestBody(c, &request); ve == false {
			return
		}

		tariff, err := h.createTariffWorkflow.Exec(c.Request.Context(), createRequestToParams(request))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.SuccessCreated(c, tariffToData(tariff))
	}
}

// @Summary		Изменить тариф
// @Tags			admin-tariff
// @Accept			json
// @Produce		json
// @Param			id		path	int				true	"ID тарифа"
// @Param			message	body	updateRequest	true	"Изменяемые поля"
// @Security		BearerAuth
// @Success		200	{object}	tariffResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs/{id} [patch]
func (h *Handler) Update() gin.HandlerFunc {
	return func(c *gin.Context) {
		id, err := validators.GetIntQueryParam(c, "id")
		if err != nil {
			response.BadRequest(c, err, nil)

			return
		}

		request := updateRequest{}
		if ve := validators.ValidateRequestBody(c, &request); ve == false {
			return
		}

		tariff, err := h.tariffService.Update(c.Request.Context(), uint(id), updateRequestToPatch(request))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}

// @Summary		Сделать тариф тарифом по умолчанию
// @Description	Снимает флаг с прежнего тарифа парковки и ставит этому — атомарно
// @Tags			admin-tariff
// @Produce		json
// @Param			id	path	int	true	"ID тарифа"
// @Security		BearerAuth
// @Success		204
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs/{id}/set-default [post]
func (h *Handler) SetDefault() gin.HandlerFunc {
	return func(c *gin.Context) {
		id, err := validators.GetIntQueryParam(c, "id")
		if err != nil {
			response.BadRequest(c, err, nil)

			return
		}

		if err := h.setDefaultTariffCommand.Exec(c.Request.Context(), uint(id)); err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.SuccessDeleted(c, nil)
	}
}
