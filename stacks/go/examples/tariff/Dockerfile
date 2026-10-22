# syntax=docker/dockerfile:1.7

FROM golang:1.27-alpine AS builder

# Приватные модули SDK; токен — секретом сборки (--secret id=netrc,src=$HOME/.netrc), не build-arg: build-arg виден в docker history
ARG GOPRIVATE=git.mpinnovations.kz/*
ENV GOPRIVATE=${GOPRIVATE} \
    GONOSUMDB=${GOPRIVATE} \
    GOAUTH=netrc \
    CGO_ENABLED=0

WORKDIR /build

RUN apk add --no-cache ca-certificates tzdata git

COPY go.mod go.sum ./
RUN --mount=type=secret,id=netrc,target=/root/.netrc \
    --mount=type=cache,target=/go/pkg/mod \
    go mod download

COPY . .

RUN --mount=type=cache,target=/go/pkg/mod \
    --mount=type=cache,target=/root/.cache/go-build \
    go build -ldflags="-s -w" -o /out/service . && \
    go build -ldflags="-s -w" -o /out/console ./cmd/console

FROM scratch

COPY --from=builder /out/service /service
COPY --from=builder /out/console /console
COPY --from=builder /usr/share/zoneinfo /usr/share/zoneinfo
COPY --from=builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt

ENV TZ=Asia/Almaty

USER 65532:65532

EXPOSE 8090

# Консоль: docker run --entrypoint /console <image> migrate up
ENTRYPOINT ["/service"]
