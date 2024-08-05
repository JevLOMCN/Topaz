FROM --platform=linux/amd64 golang:1.22.2-alpine AS builder

WORKDIR /b
COPY . .
RUN go mod download
RUN go build -o ./build/api cmd/ws/main.go

FROM  --platform=linux/amd64 gcr.io/distroless/base-debian12

WORKDIR /app
COPY --from=builder /b/build/api /app/.run
CMD ["/app/.run"]


