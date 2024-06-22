package user_domain

import (
	"encoding/json"
	"fmt"
	"log/slog"
	"net/http"
	"net/url"
	"rint-api-go/api/domains/_core"
	"strings"

	"github.com/go-chi/chi/v5"
)

type UserRoute struct{}

func New() *UserRoute {
	return &UserRoute{}
}

//
// type Account struct {
// 	Account          string `json:"account"`
// 	Email            string `json:"email"`
// 	Password         string `json:"password"`
// 	ConfirmPassword  string `json:"password2"`
// 	SecurityQuestion string `json:"question"`
// 	SecurityAnswer   string `json:"answer"`
// 	EmailCode        string `json:"ecode"`
// 	GRecaptcha       string `json:"g-recaptcha-response"`
// 	ReferralCode     string `json:"d"`
// }

type APIResponse struct {
	Code int    `json:"code"`
	Msg  string `json:"msg"`
	Data string `json:"data"`
}

type MailCodeResponse struct {
	Success   bool   `json:"success"`
	Message   string `json:"message"`
	SessionID string `json:"session_id"`
}

func jsonToFormData(r *http.Request) (url.Values, error) {

	//json to form-url-encoded
	var dataStruct map[string]string
	if parsingErr := json.NewDecoder(r.Body).Decode(&dataStruct); parsingErr != nil {

		return url.Values{}, parsingErr
	}

	formData := url.Values{}

	for k, v := range dataStruct {
		formData.Set(k, v)
	}
	return formData, nil

}

func (domain *UserRoute) GetRoutes() chi.Router {

	r := chi.NewRouter()

	r.Post("/GetMailCode", func(w http.ResponseWriter, r *http.Request) {

		formData, fdErr := jsonToFormData(r)

		if fdErr != nil {
			slog.Error("error=", fdErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		encodedData := formData.Encode()

		req, reqErr := http.NewRequest("POST", "https://dreamscapemir.com/web/mail.php", strings.NewReader(encodedData))

		if reqErr != nil {
			slog.Error("error=", reqErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		req.Header.Add("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8")

		cli := http.Client{}
		resp, respErr := cli.Do(req)

		if respErr != nil {
			slog.Error("error=", respErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		defer resp.Body.Close()

		//get response Headers

		setCookieValues := resp.Header.Values("Set-Cookie")
		phpSessIDValue := ""

		for _, v := range setCookieValues {
			if strings.Contains(v, "PHPSESSID") {
				ss := strings.Split(v, ";")
				for _, s := range ss {
					if strings.Contains(s, "PHPSESSID") {
						phpSessIDValue = strings.Split(s, "PHPSESSID=")[1]
						fmt.Println("php session id =", phpSessIDValue)
					}
				}
				break
			}
		}

		jsonResponseData := APIResponse{}
		if jsonError := json.NewDecoder(resp.Body).Decode(&jsonResponseData); jsonError != nil {

			slog.Error("resp error=", jsonError)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		if jsonResponseData.Code != 200 && jsonResponseData.Code != 0 {

			slog.Error("error=", jsonResponseData.Msg, "")
			_core.Json(w, _core.NewDefaultResponse(false, jsonResponseData.Msg), 500)
			return
		}

		_core.Json(w, MailCodeResponse{
			Success:   true,
			Message:   jsonResponseData.Msg,
			SessionID: phpSessIDValue,
		}, 200)
	})

	r.Post("/register", func(w http.ResponseWriter, r *http.Request) {

		formData, fdErr := jsonToFormData(r)

		if fdErr != nil {
			slog.Error("error=", fdErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		fmt.Println("SessionID:", formData.Get("sessID"))
		encodedData := formData.Encode()

		req, reqErr := http.NewRequest("POST", "https://dreamscapemir.com/web/register.php", strings.NewReader(encodedData))

		if reqErr != nil {
			slog.Error("error=", reqErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		req.Header.Add("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8")
		req.Header.Add("Cookie", fmt.Sprintf("PHPSESSID=%s;", formData.Get("sessID")))

		cli := http.Client{}
		resp, respErr := cli.Do(req)

		if respErr != nil {
			slog.Error("error=", respErr)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		defer resp.Body.Close()

		// bres, bErr := io.ReadAll(resp.Body)
		//
		// if bErr != nil {
		// 	slog.Error("error=", bErr)
		// 	_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
		// 	return
		// }

		jsonResponseData := APIResponse{}
		if jsonError := json.NewDecoder(resp.Body).Decode(&jsonResponseData); jsonError != nil {

			slog.Error("resp error=", jsonError)
			_core.Json(w, _core.NewDefaultResponse(false, "server error"), 500)
			return
		}

		if jsonResponseData.Code != 200 && jsonResponseData.Code != 0 {

			slog.Error("error=", jsonResponseData.Msg, "")
			_core.Json(w, _core.NewDefaultResponse(false, jsonResponseData.Msg), 500)
			return
		}

		_core.Json(w, _core.NewDefaultResponse(true, "success"), 200)
	})

	return r

}
