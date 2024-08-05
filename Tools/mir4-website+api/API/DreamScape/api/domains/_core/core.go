package _core

import (
	"encoding/json"
	"log/slog"
	"net/http"
)

type DefRes struct {
	Success bool   `json:"success"`
	Message string `json:"message"`
}

func NewDefaultResponse(success bool, message string) DefRes {
	return DefRes{
		Success: success,
		Message: message,
	}
}

func Json(writer http.ResponseWriter, structData interface{}, httpStatus int) {
	encodedJSON, err := json.Marshal(&structData)

	if err != nil {
		slog.Error("Invalid JSON Data: %+v", err)
		writer.WriteHeader(http.StatusInternalServerError)
		return
	}

	writer.Header().Set("Content-Type", "application/json")
	writer.WriteHeader(httpStatus)
	writer.Write(encodedJSON)

}
