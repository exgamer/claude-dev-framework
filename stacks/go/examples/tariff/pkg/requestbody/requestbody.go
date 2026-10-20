package requestbody

import (
	"bytes"
	"errors"
	"io"
	"net/http"
)

// DefaultLimit Предел тела запроса, которое middleware читает целиком (скоуп парковок, подпись)
const DefaultLimit = 1 << 20

// ErrTooLarge Тело больше предела — клиенту 413
var ErrTooLarge = errors.New("request body exceeds limit")

// ReadLimited Тело запроса не больше limit байт, возвращается в запрос для следующих обработчиков; нет тела — nil, Body остаётся http.NoBody
func ReadLimited(w http.ResponseWriter, r *http.Request, limit int64) ([]byte, error) {
	if r.Body == nil || r.Body == http.NoBody {
		return nil, nil
	}

	body, err := io.ReadAll(http.MaxBytesReader(w, r.Body, limit))

	var tooLarge *http.MaxBytesError
	if errors.As(err, &tooLarge) {
		return nil, ErrTooLarge
	}

	if err != nil {
		return nil, err
	}

	// Пустое тело — NoBody: код, который решает по Body != http.NoBody, не принимает его за присланное
	if len(body) == 0 {
		r.Body = http.NoBody

		return nil, nil
	}

	r.Body = io.NopCloser(bytes.NewReader(body))

	return body, nil
}
