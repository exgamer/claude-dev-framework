package tariff

import (
	"errors"
	"net/http"

	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/response"
	"git.mpinnovations.kz/mps/go-packages/gosdk-http-core/pkg/validators"
	"github.com/gin-gonic/gin"

	tariffdomain "example.com/parking-service/internal/domains/billing/tariff"
	adminmiddleware "example.com/parking-service/internal/entrypoints/admin/http/middleware"
	tariffworkflow "example.com/parking-service/internal/workflows/billing/tariff"
)

func NewHandler(
	tariffService *tariffdomain.Service,
	getDefaultTariffQuery *tariffdomain.GetDefaultTariffQuery,
	setDefaultTariffCommand *tariffdomain.SetDefaultTariffCommand,
	createTariffWorkflow *tariffworkflow.CreateTariffWorkflow,
) *Handler {
	return &Handler{
		tariffService:           tariffService,
		getDefaultTariffQuery:   getDefaultTariffQuery,
		setDefaultTariffCommand: setDefaultTariffCommand,
		createTariffWorkflow:    createTariffWorkflow,
	}
}

type Handler struct {
	tariffService           *tariffdomain.Service
	getDefaultTariffQuery   *tariffdomain.GetDefaultTariffQuery
	setDefaultTariffCommand *tariffdomain.SetDefaultTariffCommand
	createTariffWorkflow    *tariffworkflow.CreateTariffWorkflow
}

// @Summary		Список тарифов
// @Description	Только тарифы парковок, доступных администратору; parking_id недоступной парковки — 403
// @Tags			admin-tariff
// @Produce		json
// @Param			parking_id	query	int	false	"ID парковки"
// @Param			page		query	int	false	"Страница"
// @Param			per_page	query	int	false	"Записей на странице (до 100)"
// @Security		BearerAuth
// @Success		200	{object}	tariffListResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs [get]
func (h *Handler) Index() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := indexRequest{}
		if ok := validators.ValidateRequestQuery(c, &request); !ok {
			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		result, err := h.tariffService.Paginated(c.Request.Context(), indexRequestToSearch(request, scope))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, paginatedToData(result))
	}
}

// @Summary		Тариф по ID
// @Description	Тариф парковки, недоступной администратору, — 404
// @Tags			admin-tariff
// @Produce		json
// @Param			id	path	int	true	"ID тарифа"
// @Security		BearerAuth
// @Success		200	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
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

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		tariff, err := h.tariffService.GetByID(c.Request.Context(), uint(id), scope)
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}

// @Summary		Тариф по умолчанию парковки
// @Description	Действующий тариф по умолчанию; нет или отключён — 404; parking_id недоступной парковки — 403
// @Tags			admin-tariff
// @Produce		json
// @Param			parking_id	query	int	true	"ID парковки"
// @Security		BearerAuth
// @Success		200	{object}	tariffResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs/default [get]
func (h *Handler) Default() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := defaultRequest{}
		if ok := validators.ValidateRequestQuery(c, &request); !ok {
			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		tariff, err := h.getDefaultTariffQuery.Exec(c.Request.Context(), request.ParkingID, scope)
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}

// @Summary		Создать тариф
// @Description	Тариф создаётся не дефолтным; дефолт назначается отдельным запросом. Валюта — валюта парковки, цена — не выше лимита парковки в реестре парковок, иначе 422; parking_id недоступной парковки — 403
// @Tags			admin-tariff
// @Accept			json
// @Produce		json
// @Param			message	body	createRequest	true	"Тариф"
// @Security		BearerAuth
// @Success		201	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		413	{object}	structures.BadRequestErrorResponse
// @Failure		415	{object}	structures.BadRequestErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs [post]
func (h *Handler) Create() gin.HandlerFunc {
	return func(c *gin.Context) {
		request := createRequest{}
		if ok := validators.ValidateRequestBody(c, &request); !ok {
			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		if !scope.Allows(request.ParkingID) {
			response.ErrorResponseUntrackableSentry(c, http.StatusForbidden, errors.New("нет доступа к парковке"), nil)

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
// @Description	Тариф парковки, недоступной администратору, — 404
// @Tags			admin-tariff
// @Accept			json
// @Produce		json
// @Param			id		path	int				true	"ID тарифа"
// @Param			message	body	updateRequest	true	"Изменяемые поля"
// @Security		BearerAuth
// @Success		200	{object}	tariffResponse
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		413	{object}	structures.BadRequestErrorResponse
// @Failure		415	{object}	structures.BadRequestErrorResponse
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
		if ok := validators.ValidateRequestBody(c, &request); !ok {
			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		tariff, err := h.tariffService.Update(c.Request.Context(), uint(id), scope, updateRequestToPatch(request))
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.Success(c, tariffToData(tariff))
	}
}

// @Summary		Сделать тариф тарифом по умолчанию
// @Description	Снимает флаг с прежнего тарифа парковки и ставит этому — атомарно; тариф парковки, недоступной администратору, — 404; параллельная смена тарифа по умолчанию той же парковки — 422
// @Tags			admin-tariff
// @Produce		json
// @Param			id	path	int	true	"ID тарифа"
// @Security		BearerAuth
// @Success		204
// @Failure		400	{object}	structures.BadRequestErrorResponse
// @Failure		401	{object}	structures.BadRequestErrorResponse
// @Failure		403	{object}	structures.ForbiddenErrorResponse
// @Failure		404	{object}	structures.NotFoundErrorResponse
// @Failure		422	{object}	structures.ValidationErrorResponse
// @Failure		500	{object}	structures.InternalServerResponse
// @Router			/api/v1/admin/tariffs/{id}/set-default [post]
func (h *Handler) SetDefault() gin.HandlerFunc {
	return func(c *gin.Context) {
		id, err := validators.GetIntQueryParam(c, "id")
		if err != nil {
			response.BadRequest(c, err, nil)

			return
		}

		scope, err := adminmiddleware.ParkingScopeFromContext(c.Request.Context())
		if err != nil {
			response.ErrorResponse(c, err)

			return
		}

		if err := h.setDefaultTariffCommand.Exec(c.Request.Context(), uint(id), scope); err != nil {
			response.ErrorResponse(c, err)

			return
		}

		response.SuccessDeleted(c, nil)
	}
}
