package signature

import "time"

// Parsed Разобранный заголовок подписи
type Parsed struct {
	Timestamp int64
	Nonce     string
	// Signature hex в нижнем регистре
	Signature string
}

// Message Подписываемая часть HTTP-запроса
type Message struct {
	Method string
	// Path Путь вместе с query
	Path string
	Body []byte
}

// Request Сообщение с меткой времени и nonce отправителя
type Request struct {
	Timestamp int64
	Nonce     string
	Message   Message
}

// VerifyParams Проверка подписи принятого запроса
type VerifyParams struct {
	Secret  string
	Parsed  Parsed
	Message Message
	// Tolerance Допустимое расхождение времени подписи с Now в обе стороны
	Tolerance time.Duration
	Now       time.Time
}
