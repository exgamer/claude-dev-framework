package admin

import "context"

type contextKey struct{}

// WithAdmin Контекст с аутентифицированным администратором
func WithAdmin(ctx context.Context, admin *Admin) context.Context {
	return context.WithValue(ctx, contextKey{}, admin)
}

// FromContext Текущий аутентифицированный администратор; nil — в контексте его нет
func FromContext(ctx context.Context) *Admin {
	admin, _ := ctx.Value(contextKey{}).(*Admin)

	return admin
}
