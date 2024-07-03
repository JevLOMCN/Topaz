;
(function($) {
    $.fn.extend({
        cmmValidator: function(obj) {
            var $this = $(this);
            var defaults = {
                ime: true,
                /*eventType: 'keyup blur keypress',
                keycodeGubun: false,*/
                eventType: 'keydown keyup blur',
                keycodeGubun: true
            };

            function CmmValidator($this) {
                this.el = $this;
                this.obj = $.extend(true, defaults, obj);
                this.opt = {
                    imeArry: ['number', 'tel', 'ko', 'en', 'email', 'ennumber', 'konumber', 'etc', 'enetc', 'koetc'],
                    ankeycode: [9, 8, 13, 16, 20, 21, 35, 36, 37, 38, 39, 40],
                    kokeycode: 229
                };
                this.input = null;
                this.inputArry = [];
                this.dataName = 'params';
                this.title = '';
                this.clearchk = false;
                this.init();
            };
            CmmValidator.prototype = {
                init: function() {
                    var _this = this;
                    this.set();
                    this.fnIme();
                    window.addEventListener('input', function() {
                        _this.oninput(_this);
                    });
                },
                set: function() {
                    var _this = this;
                    var clsName = '';
                    this.el.find('input').each(function() {
                        var $this = $(this);
                        var $data = $this.data(_this.dataName);
                        if($data) {
                            if($data.required) {
                                $this.attr('required', $data.required);
                            }
                        }
                        if($data && $data.ime && !$this.is('[type="radio"]') && !$this.is('[type="checkbox"]')) {
                            switch($data.ime) {
                                case _this.opt.imeArry[2]:
                                    clsName = 'IME_KO';
                                    break;
                                case _this.opt.imeArry[3]:
                                    clsName = 'IME_ONLY_EN';
                                    break;
                            }
                            $this.addClass(clsName);
                        }
                    });
                },
                fnImeExp: function(str, keycode) {
                    var exp = '';
                    var keygubun = '';
                    var callback = null;
                    if(str.indexOf(',') != -1) {
                        var arry = str.split(',');
                        var strArry = '';
                        for(var i = 0; i < arry.length; i++) {
                            strArry += arry[i];
                        }
                    } else {
                        strArry = str;
                    }
                    switch(strArry) {
                        case this.opt.imeArry[0]: //number
                        case this.opt.imeArry[1]: //tel
                            keygubun = (keycode >= 48 && keycode <= 57) || (keycode >= 96 && keycode <= 105) && keycode != this.opt.kokeycode;
                            exp = /[^0-9]/gi;
                            break;
                        case this.opt.imeArry[2]: //ko
                            keygubun = keycode == this.opt.kokeycode;
                            exp = /[^ㄱ-ㅎ|ㅏ-ㅣ|가-힣]/gi;
                            break;
                        case this.opt.imeArry[3]: //en
                            keygubun = keycode >= 65 && keycode <= 90 && keycode != this.opt.kokeycode;
                            exp = /[^A-Za-z]/gi;
                            break;
                        case this.opt.imeArry[4]: //email
                            exp = /[^A-Za-z|0-9|@|.]/gi;
                            break;
                        case this.opt.imeArry[5]: //ennumber
                            exp = /[^A-Za-z|0-9]/gi;
                            break;
                        case this.opt.imeArry[6]: //konumber
                            exp = /[^ㄱ-ㅎ|ㅏ-ㅣ|가-힣|0-9]/gi;
                            break;
                        case this.opt.imeArry[7]: //etc
                            exp = /[^~!@\#$%<>^&*\()\-=+_\’.,/]/gi;
                            break;
                        case this.opt.imeArry[8]: //enetc
                            exp = /[^A-Za-z|~!@\#$%<>^&*\()\-=+_\’.,/]/gi;
                            break;
                        case this.opt.imeArry[9]: //koetc
                            exp = /[^ㄱ-ㅎ|ㅏ-ㅣ|가-힣|~!@\#$%<>^&*\()\-=+_\’.,/]/gi;
                            break;
                    }
                    callback = function($input) {
                        var $val = $input.val();
                        $input.val($val.replace(exp, ''));
                    };
                    return {
                        exp: exp,
                        keygubun: keygubun,
                        callback: callback
                    };
                },
                oninput: function(_this) {
                    var $input = _this.el.find('input[type="number"]');
                    $input.each(function() {
                        var $this = $(this);
                        var _input = $this[0];
                        if(_input.maxLength > 0) {
                            if(_input.value.length > _input.maxLength) {
                                _input.value = _input.value.slice(0, _input.maxLength);
                            }
                        }
                    });
                },
                fnIme: function($input, ime) {
                    var _this = this;
                    this.el.find('input, textarea').on(_this.obj.eventType, function(e) {
                        var $this = $(this);
                        var _bool = true;
                        if(!$this.is('[type="checkbox"]') && !$this.is('[type="radio"]')) {
                            var $val = $this.val();
                            var $data = $this.data(_this.dataName);
                            var keycode = e.keyCode;
                            var str = '';
                            if($data.ime) {
                                str = _this.fnImeExp($data.ime, keycode);
                                if(_this.obj.keycodeGubun && str.keygubun != '' && navigator.userAgent.indexOf('Mobile') == -1) {
                                    if(str.keygubun || _this.opt.ankeycode.indexOf(keycode) != -1) {
                                        _bool = true;
                                    } else {
                                        _bool = false;
                                    }
                                    if(!_bool) {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        e.returnValue = false;
                                    }
                                    if(typeof str.callback === 'function') {
                                        str.callback($this);
                                    }
                                    return _bool;
                                } else {
                                    if(str.exp.test($val)) {
                                        $(this).val($val.replace(str.exp, ''));
                                    }
                                }
                            }
                        }
                    });
                },
            };
            this.each(function() {
                $.data($(this), new CmmValidator($(this), obj));
            });
            return this;
        },
        cmmAjax: function(obj) {
            var globalBool = true;
            var args = arguments;
            var radioBool = false;
            var defaults = {
                errorCall: 'alert', //alert , append
                errorStr: 'title',
                ajax: null,
                jsonParse: false
            };

            function CmmAjax($this) {
                this.el = $this;
                this.obj = typeof obj === 'string' ? obj : typeof obj === 'number' ? $.extend(true, defaults, {
                    minlength: args[0],
                    maxlength: args[1]
                }) : $.extend(true, defaults, obj);
                this.input = null;
                this.inputArry = [];
                this.title = '';
                this.clearchk = false;
                this.dataName = 'params';
                this.opt = {
                    chkmsg: ['을(를) 선택해주세요.', '을(를) 입력해주세요.'],
                }
                this.globalBool = true;
                this.init();
                globalBool = this.globalBool;
                if(this.obj == 'result') {
                    globalBool = this.el.serializeObject();
                    if(args[1] == 'json') {
                        globalBool = JSON.stringify(globalBool);
                    }
                }
            };
            CmmAjax.prototype = {
                init: function() {
                    var _this = this;
                    var chkleng = 0;
                    if(_this.obj == 'clear') {
                        _this.clear(this.el);
                    } else if(_this.obj == 'submit') {
                        _this.submitSet(args[1]);
                    }
                    _this.clear = false;
                    //_this.el.find('input, select, textarea').each(function() {
                    _this.el.each(function() {
                        var $this = $(this);
                        var $data = $this.data(_this.dataName);
                        chkleng++;
                        if($data && $data.required) {
                            _this.input = $this;
                            _this.clear = false;
                            _this.chk();
                            if(_this.clear) {
                                _this.clearchk = false;
                                return false;
                            } else {
                                _this.clearchk = true;
                                if($this.is('input[type="checkbox"]') || $this.is('input[type="radio"]')) {
                                    _this.input = _this.input.prop('checked') ? _this.input : null;
                                }
                                if(_this.input) {
                                    _this.inputArry.push(_this.input);
                                }
                            }
                        }
                    });
                },
                chk: function() {
                    var _this = this;
                    if(this.obj == 'tel') {
                        _this.typeInputGubun(_this.el);
                    }
                    if(this.input.is('input[type="checkbox"]')) {
                        this.errorFun(this.input.prop('checked'), this.opt.chkmsg[0]);
                    } else if(this.input.is('input[type="radio"]')) {
                        var $name = this.input.attr('name');
                        if(this.input.is(':checked') && this.input.data(_this.dataName) && this.input.data(_this.dataName).required) {
                            radioBool = true;
                        }
                        this.errorFun(radioBool, this.opt.chkmsg[0]);
                    } else {
                        var inputbool = this.input.val();
                        var trim = inputbool.replace(/\s+/, '');
                        if(this.input.is('textarea') && trim == '') {
                            inputbool = false;
                        }
                        if(this.obj.maxlength && this.obj.minlength && (this.obj.minlength > this.input.val().length || this.obj.maxlength < this.input.val().length)) {
                            //글자수체크 함수명 뒤에 arguments[0] : min , arguments[1] : max
                            inputbool = false;
                        }
                        this.errorFun(inputbool, this.opt.chkmsg[1]);
                    }
                },
                errorFun: function(bool, bmsg) {
                    if(!bool) {
                        /*switch(this.obj.errorCall) {
                            case 'alert':
                                //alert('\'' + this.title + '\'' + bmsg);
                                //console.log('\'' + this.title + '\'' + bmsg);
                                break;
                            case 'layerpop':
                                //console.log(this.data(this.dataName));
                                console.log(this.input.data('fun'))
                                break;
                        }*/
                        //this.input.focus();
                        this.clear = true;
                        this.globalBool = false;
                    }
                },
                typeInputGubun: function($this) {
                    //var $this = this.el;
                    var $val = $this.val();
                    var bool = false;
                    if($this.data(this.dataName).ime == 'tel' && $val) {
                        if($this.val().indexOf('010') == 0 || $this.val().indexOf('011') == 0) {
                            bool = true;
                        }
                    }
                    this.errorFun(bool, this.opt.chkmsg[1]);
                },
                submitSet: function(_args) {
                    if(!_args || typeof _args === 'object') {
                        var ajaxobj = $.extend(true, {
                            url: '',
                            type: 'post',
                            data: this.el.serializeObject(),
                            dataType: 'json',
                            success: function(data) {},
                            error: function(r, s, e) {
                                console.error("cmmAjax.submitSet() ERROR : \ncode:" + r.status + "\n" + "message:" + r.responseText + "\n" + "error:" + e);
                            }
                        }, _args);
                        if(!this.obj.jsonParse) {
                            //ajaxobj.data = JSON.stringify(ajaxobj.data);
                        }
                        $.ajax(ajaxobj);
                    } else if(typeof _args === 'function') {
                        _args();
                    }
                },
                clear: function($this) {
                    $this.find('input, textarea, select').each(function() {
                        var $this = $(this);
                        if($this.is('[type="checkbox"]') || $this.is('[type="radio"]')) {
                            $this.prop('checked', false);
                        } else {
                            $this.val('');
                        }
                    });
                }
            };
            this.each(function() {
                $.data($(this), new CmmAjax($(this), obj));
            });
            return globalBool;
        },
        serializeObject: function() {
            var $this = $(this);
            var o = {};
            var a = this.serializeArray();
            $.each(a, function() {
                if(o[this.name]) {
                    if(!o[this.name].push) {
                        o[this.name] = [o[this.name]];
                    }
                    o[this.name].push(this.value || '');
                } else {
                    o[this.name] = this.value || '';
                }
            });
            return o;
        },
    });
})(jQuery);