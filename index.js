$(function() {
    var URL_CACHEREFS = 'cache/cache-urls.json';
    var URL_CMD = 'cmd.php?name=';
    var A_CMD = ['authAccount', 'createAccount', 'createSubscr', 'setSubscrProfile'];
    var MARK_KW_NEG = '~:';
    var MARK_KW_XTRA = 'extra:';
    var B_AJAXCACHE = false;
    var B_STAMP_ETAG = true;
    var INT_POLL_MIN = 60;
    var INT_POLL_LONG = 600;
    var INT_POLL_SHORT = 120;
    var INT_SHARED = 30;
    var N_ITEMS_PRECUT = 5;
    var NAME_COOKIE = 'ff_state';
    var EXP_COOKIE = 30;

    var aTplUrls = 0;
    var urlShared = '';
    var urlAcct = '';
    var urlProfile = '';
    var urlResult = '';
    var urlResultStamp = '';
    var nFeedUrls = 0;
    var sAcct = '';
    var iAcct = false;
    var iSubscr = '';
    var aSubscrIds = [];
    var aSubscrTitles = [];
    var bNewSubscr = false;
    var iErrCode = 0;
    var nItems = 0;
    var sStampResult = 0;
    var sStampShared = 0;
    var nIntPolling = INT_POLL_LONG * 1000;
    var iPolling = 0;
    var nIdTimer = 0;
    var bFocus = true;
    var bFocusUpd = false;
    var oItemCntr = $('#items');
    var oTpls = $('#elemTpls').children();
    var oItemTpl = oTpls.eq(0);
    var oItemDate = oItemTpl.find('span.item-date');
    var oItemTitle = oItemTpl.find('a.item-title');
    var oItemLink = oItemTpl.find('a.item-link');
    var oItemCont = oItemTpl.find('.item-content');
    var oChkboxTpl = oTpls.eq(1);
    var oChkboxElem = oChkboxTpl.find('input');
    var oChkboxTitle = oChkboxTpl.find('span');
    var oChkboxLink = oChkboxTpl.find('a');
    var sTextUnlogged = $('#acctId').html();
    var sTextEmpty = oItemCntr.html();
    var nStateIntPoll = INT_POLL_SHORT;
    var nStateItemsBuff = 0;
    var nStateSound = 0;
    var nStateSubscr = 0;
    var oAudio = null;


    loadState();
    showUnlogged();
    createSound();
    queryInit();

    $('form').submit(function() {return false});

    $('div.navbar li.auth > a').click(function() {
        var iType = ($(this).attr('href') == '#signup')? 1:0;
        $('#authLabel span').addClass('hide').eq(iType).removeClass('hide');
        $('#authType').val(iType);
        $('#auth div.alert').addClass('hide');
        stopPolling();
        $('#auth').modal('show');
        return false;
    });

    $('#auth button.btn-primary').click(function() {
        var iType = ($('#authType').val() == 1)? 1 : 0;
        var oBtn = $(this); oBtn.button('loading');
        iErrCode = 0;
        queryAuth(iType, $('#authForm').serialize(), true)
        .complete(function() {
            oBtn.button('reset');
            if (sAcct) {
                $('#auth').modal('hide');
                return;
            }
            var oDiv = $('#auth div.alert');
            oDiv.find('span').addClass('hide')
            .eq(iType? (iErrCode < 4? iErrCode+1 : 1) : 0).removeClass('hide');
            oDiv.removeClass('hide');
        });
    });

    $('#logout').click(function() {
        stopPolling();
        $.ajax({
            url: URL_CMD,
            type: 'POST',
            data: 'logout=1',
            dataType: 'text'
        })
        .complete(function() {
            queryPolling();
        });
        sAcct = '';
        setAcctTitle();
        resetSubscrList();
        showUnlogged();
        return false;
    });

    $('#subscrSel').change(function() {
        stopPolling();
        this.blur();
        iSubscr = $(this).prop('selectedIndex');
        setSubscr();
        queryPolling();
    });

    $('#subscrNew').click(function() {
        stopPolling();
        var iRet = 0;
        $.ajax({
            url: URL_CMD+ A_CMD[2],
            type: 'POST',
            data: '',
            dataType: 'text',
            success: function(ret) { iRet = ret; }
        })
        .complete(function() {
            (iRet == 1)? queryAfterNewSubscr() : alertEx(iRet);
        });
        return false;
    });

    $('#subscrProfile').on('show', function() {
        stopPolling();
        var b = bNewSubscr; bNewSubscr = false;
        resetProfile(b);
        if (!urlProfile || b) return;
        queryStatic(urlProfile, outProfile);
    });

    $('#subscrProfile').on('hidden', function() {
        queryPolling();
    });

    $('#subscrProfile button.btn-primary').click(function() {
        var sData = prepareProfile();
        if (!sData) return;
        var oBtn = $(this); oBtn.button('loading');
        var bOk = false;
        $.ajax({
            url: URL_CMD+ A_CMD[3],
            type: 'POST',
            data: sData,
            dataType: 'text',
            success: function(ret) { if (ret == '1') bOk = true; }
        })
        .complete(function() {
            oBtn.button('reset');
            if (bOk) queryStatic(urlAcct, setSubscrList);
            alertEx(bOk? 5:4);
        });
        $('#subscrProfile').modal('hide');
    });

    $('#options').on('show', function() {
        stopPolling();
        outOptions();
    });

    $('#options button.btn-primary').click(function() {
        saveOptions();
        $('#options').modal('hide');
        queryPolling();
    });

    $('#alert').on('hidden', function() {
        queryPolling();
    });

    $(window).focus(function() {
        var b = bFocus, b2 = bFocusUpd;
        bFocus = true; bFocusUpd = false;
        if (b || !b2) return;
        nStateSound? queryPolling() : postponePolling();
    });

    $(window).blur(function() {
        bFocus = false;
        bFocusUpd = nStateSound? false : Boolean(nIdTimer);
        if (!nStateSound && bFocusUpd) stopPolling();
    });


    function queryInit() {
        queryStatic(URL_CACHEREFS, setInit);
    }

    function setInit(aData) {
        if (!(aData && typeof aData == 'object' && aData.length == 5)) return;
        aTplUrls = aData;
        urlShared = aData[0];
        queryAuth(0, null, false);
    }

    function queryAuth(iType, sData, bInited) {
        return $.ajax({
            url: URL_CMD+ A_CMD[iType],
            type: 'POST',
            data: sData? sData: '',
            dataType: 'json',
            success: setAcct
        })
        .complete(function() {
            if (iAcct !== false || bInited) return;
            resetSubscrList();
            queryPolling();
        });
    }

    function setAcct(oData) {
        if (!(oData && typeof oData == 'object')) return;
        if ('error' in oData) {
            iErrCode = oData.error; return;
        }
        if (!('i' in oData && 'id' in oData)) return;
        iAcct = oData.i;
        sAcct = oData.id;
        setAcctTitle();
        showLogged();
        if (!aTplUrls) return;
        urlAcct = getCacheUrl(aTplUrls[1], iAcct);
        iSubscr = '';
        queryStatic(urlAcct, setSubscrList)
        .complete(function() {
            (aSubscrIds.length)? setSubscr() : resetSubscrList(nItems);
            queryPolling();
        });
    }

    function queryAfterNewSubscr() {
        var nPrev = aSubscrIds.length;
        if (!nPrev) iSubscr = 0;
        queryStatic(urlAcct, setSubscrList)
        .complete(function() {
            if (!nPrev)
                setSubscr();
            bNewSubscr = true;
            $('#subscrProfile').modal('show');
        });
    }

    function resetSubscrList(bDont) {
        aSubscrIds = aSubscrTitles = [];
        iSubscr = '';
        if (!bDont) setSubscr();
    }

    function setSubscrList(oData) {
        if (!(oData && typeof oData == 'object')) return;
        if (!('aSubscrIds' in oData && 'aSubscrTitles' in oData)) return;
        if (!oData.aSubscrIds.length) return;
        if (oData.aSubscrIds.length != oData.aSubscrTitles.length) return;
        aSubscrIds = oData.aSubscrIds;
        aSubscrTitles = oData.aSubscrTitles;
        if (iSubscr === '') {
            loadState();
            iSubscr = nStateSubscr < aSubscrIds.length? nStateSubscr : 0;
        }
        outSubscrList();
    }

    function setSubscr() {
        nItems = sStampResult = sStampShared = 0;
        outResultsEmpty();
        if (!aTplUrls) return;
        urlProfile = getCacheUrl(aTplUrls[2], iSubscr);
        urlResult = getCacheUrl(aTplUrls[3], iSubscr);
        urlResultStamp = getCacheUrl(aTplUrls[4], iSubscr);
        $('div.page-header span').addClass('hide')
        .eq(iSubscr !== ''? 1 : 0).removeClass('hide');
        if (iSubscr !== '') saveState();
    }

    function queryPolling() {
        if (urlShared && iPolling++ % INT_SHARED == 0)
            queryShared();
        stopPolling();
        if (!urlResultStamp) return;
        var bOk = false;
        queryStaticEx(urlResultStamp, 'text', sStampResult, function(txt, s) {
            if (s == sStampResult) return;
            if (!bFocus) {
                bFocusUpd = true; playSound();
                return;
            }
            sStampResult = s;
            bOk = true;
            queryResults();
        })
        .complete(function() {
            if (bOk) return;
            postponePolling();
        });
    }

    function queryShared() {
        queryStaticEx(urlShared, 'json', sStampShared, function(oData, s){
            if (s == sStampShared) return;
            sStampShared = s;
            outShared(oData);
        });
    }

    function queryResults() {
        if (!urlResult) return;
        var nPrev = nItems;
        queryStatic(urlResult, outResults)
        .complete(function() {
            if (nItems != nPrev) playSound();
            postponePolling();
        });
    }

    function queryStatic(sUrl, fnSuccess) {
        return $.ajax({
            url: sUrl,
            type: 'GET',
            dataType: 'json',
            cache: B_AJAXCACHE,
            success: fnSuccess
        });
    }

    function queryStaticEx(sUrl, sType, sStamp, fnSuccess) {
        return $.ajax({
            url: sUrl,
            type: 'GET',
            dataType: sType,
            cache: B_AJAXCACHE,
            beforeSend: function(oXhr) {
                if (B_STAMP_ETAG && sStamp != '')
                    oXhr.setRequestHeader('If-None-Match', sStamp);
            },
            success: function(data, nStatus, oXhr) {
                var s = B_STAMP_ETAG? getStampEtag(oXhr) : getStampStd(oXhr);
                if (s) fnSuccess(data, s);
            }
        });
    }

    function getStampStd(oXhr) {
        if (!oXhr) return 0;
        var s = oXhr.getResponseHeader('Last-Modified');
        return s? Date.parse(s) : 0;
    }

    function getStampEtag(oXhr) {
        if (!oXhr) return 0;
        var s = oXhr.getResponseHeader('ETag');
        return s? s : '';
    }

    function postponePolling() {
        nIdTimer= setTimeout(queryPolling, nIntPolling);
    }

    function stopPolling() {
        if (nIdTimer) clearTimeout(nIdTimer); nIdTimer = 0;
    }

    function getCacheUrl(sUrl, i) {
        return sUrl.replace(/%s/, i);
    }

    function showLogged() {
        $('li.acct-unlogged').addClass('hide');
        $('li.acct-logged').removeClass('hide');
        $('#subscrSel,#subscrEdit').addClass('hide');
        $('#subscrForm').removeClass('hide');
        nIntPolling = nStateIntPoll * 1000;
    }

    function showUnlogged() {
        $('li.acct-unlogged').removeClass('hide');
        $('li.acct-logged').addClass('hide');
        $('#subscrForm').addClass('hide');
        nIntPolling = INT_POLL_LONG * 1000;
    }

    function setAcctTitle() {
        $('#acctId').html(sAcct? sAcct : sTextUnlogged);
    }

    function outSubscrList() {
        var oCntr = $('#subscrSel');
        if (!oCntr.length) return;
        oCntr.empty();
        for(var i in aSubscrIds) {
            $('<option'+ (i == iSubscr? ' selected': '')+ '></option>')
            .text(aSubscrTitles[i]? aSubscrTitles[i] : 'Untitled')
            .val(aSubscrIds[i])
            .appendTo(oCntr);
        }
        $('#subscrSel,#subscrEdit').removeClass('hide');
    }

    function outResultsEmpty() {
        oItemCntr.empty().html(sTextEmpty);
    }

    function outResults(aData) {
        if (!oItemCntr || !aData || !aData.length) return false;
        var nData = aData.length;
        var nTsLast = 0, aItems;
        if (!nItems)
            oItemCntr.empty();
        else {
            aItems = oItemCntr.children('.item');
            var nPre = Math.min(nItems, N_ITEMS_PRECUT);
            nTsLast = aItems.eq(nPre).data('timestamp');
            nItems -= nPre;
            nItems? aItems.filter(':lt('+nPre+')').remove() : aItems.remove();
        }
        for (var n = 0, i = nData- 1; i >= 0; i--) {
            var oRec = aData[i];
            if (!oRec || !oRec.date || !oRec.content) continue;
            var t = parseInt(oRec.date);
            if (nTsLast && t <= nTsLast) continue;
            var nTs = t;
            n++; nItems++;
            var d = new Date(nTs* 1000);
            oItemDate.attr('title', d.toLocaleString())
            .html(fixTime(d.toLocaleTimeString()));
            if (oRec.link) oItemLink.attr('href', oRec.link).removeClass('hide');
            if (oRec.title) oItemTitle.html(oRec.title);
            var sId = 'item'+(nItems);
            oItemTitle.attr('href', '#'+sId);
            oItemCont.html(oRec.content+ (oRec.xtras? '<hr>'+oRec.xtras:''))
            .attr('id', sId).find('a[href]').attr('target', '_blank');
            oItemTpl.clone(true)
            .data('timestamp', nTs)
            .prependTo(oItemCntr).slideDown();
        }
        if (nStateItemsBuff) {
            aItems = oItemCntr.children('.item');
            if (aItems.length > nStateItemsBuff)
                aItems.filter(':gt('+(nStateItemsBuff-1)+')').remove();
        }
        return n? n : false;
    }

    function fixTime(s) {
        var arr = s.match(/^(\d{1,2}\D\d{2})/);
        return arr? (arr[1].length == 5? arr[1] : '0'+arr[1]) : s;
    }

    function outShared(oData) {
        if (!(oData.aFeedUrls && oData.aFeedUrls.length)) return;
        var aUrls = oData.aFeedUrls;
        nFeedUrls = aUrls.length;
        var aTitles = oData.aFeedTitles && oData.aFeedTitles.length == nFeedUrls?
            oData.aFeedTitles : 0;
        var oCntr = $('#profileForm #chkboxset');
        if (!oCntr.length) return;
        oCntr.empty();
        var n = 0;
        for(var i in aUrls) {
            oChkboxElem.data('feedno', i);
            var s = aTitles && aTitles[i]? aTitles[i] : getUrlHost(aUrls[i]);
            oChkboxTitle.html(++n+ '. '+ s);
            oChkboxLink.attr('href', aUrls[i]);
            oChkboxTpl.clone(true).appendTo(oCntr);
        }
        $('#profileForm fieldset').eq(1).removeClass('hide');
    }

    function getUrlHost(s) {
        var arr = s.match(/^[a-z]+\:\/\/(?:www\.|)([^\/]+)/);
        return arr? arr[1] : 'untitled';
    }

    function resetProfile(bNew) {
        var oForm = $('#profileForm');
        oForm.find('textarea,input').val('');
        oForm.find('#chkboxset input').prop('checked', false);
        var i = bNew? aSubscrIds.length-1 : iSubscr;
        oForm.find('input[type=hidden]').eq(0).val(aSubscrIds[i]);
    }

    function outProfile(oData) {
        if (!(oData && 'sTitle' in oData)) return false;
        if (!('aKeywords' in oData && 'aKeywordTypes' in oData)) return false;
        var oForm = $('#profileForm');
        if (!oForm.length) return false;
        oForm.find('input[type=text]').val(oData.sTitle);
        var a1 = oData.aKeywords, a2 = oData.aKeywordTypes, i;
        if (a1.length && a2.length == a1.length) {
            var s = '';
            for (i in a1) {
                var t = a2[i];
                s += '\n'+ (t & 1? MARK_KW_NEG : '')+ (t & 2? MARK_KW_XTRA : '')+
                    a1[i];
            }
            oForm.find('textarea').eq(0).val(s.substr(1));
        }
        var aChkboxes = oForm.find('#chkboxset input');
        if (!(oData.aFeedIds && oData.aFeedIds.length)) return true;
        var aIds = oData.aFeedIds;
        for (i in aIds) {
            aChkboxes.eq(aIds[i]).prop('checked', true);
        }
        return true;
    }

    function prepareProfile() {
        var oForm = $('#profileForm');
        if (!oForm.length) return false;
        var aChkboxes = oForm.find('#chkboxset input:checked'), sIds = '';
        if (aChkboxes.length < nFeedUrls) {
            aChkboxes.each(function(i) {
                sIds += ' ' + $(this).data('feedno');
            });
        }
        oForm.find('input[type=hidden]').eq(1).val(sIds? sIds.substr(1):'');
        return oForm.serialize();
    }

    function outOptions() {
        var aElems = $('#optionsForm .optionElem');
        if (aElems.length < 3) return;
        aElems.eq(0).val(nStateIntPoll);
        aElems.eq(1).val(nStateItemsBuff);
        aElems.eq(2).prop('checked', nStateSound == 1);
    }

    function saveOptions() {
        var aElems = $('#optionsForm .optionElem'), n;
        if (Number(n = aElems.eq(0).val()) >= INT_POLL_MIN) nStateIntPoll = Number(n);
        if (Number(n = aElems.eq(1).val()) >= 10) nStateItemsBuff = Number(n);
        nStateSound = aElems.eq(2).prop('checked')? 1 : 0;
        saveState();
    }

    function saveState() {
        nStateSubscr = iSubscr? Number(iSubscr) : 0;
        setCookie([nStateIntPoll, nStateItemsBuff, nStateSound, nStateSubscr]);
    }

    function loadState() {
        var arr = getCookie(), v;
        if (!(arr && arr.length >= 4)) return;
        if (!isNaN(v = Number(arr[0])) && v >= INT_POLL_MIN)
            nStateIntPoll = v;
        if (!isNaN(v = Number(arr[1])) && v >= 10)
            nStateItemsBuff = v;
        if (!isNaN(v = Number(arr[2])))
            nStateSound = v;
        if (!isNaN(v = Number(arr[3])) && v >= 0)
            nStateSubscr = v;
    }

    function setCookie(arr) {
        var s = '';
        for (var i in arr)
            if (typeof arr[i] == 'number') s += '_'+ arr[i];
        if (!s) return;
        var d = new Date();
        d.setDate(d.getDate()+ EXP_COOKIE);
        document.cookie= NAME_COOKIE+ '='+ s.substr(1)+
            '; expires='+ d.toUTCString();
    }

    function getCookie() {
        var txt = document.cookie;
        var i1 = txt.indexOf(NAME_COOKIE+ '='), ch;
        if (!(i1 == 0 || i1 > 0 && ((ch = txt.charAt(i1-1)) == ' ' || ch == ';')))
            return false;
        i1 += NAME_COOKIE.length+ 1;
        var i2 = txt.indexOf(';', i1);
        if (i2 <= i1) i2 = txt.length;
        return txt.substring(i1, i2).split('_');
    }

    function alertEx(i) {
        var oPopup = $('#alert');
        oPopup.find('.modal-body > span').addClass('hide').eq(i).removeClass('hide');
        oPopup.modal();
    }

    function createSound() {
        if (nStateSound != 1) return;
        oAudio = new Audio();
        var codec = !oAudio.canPlayType? false :
            oAudio.canPlayType('audio/ogg;')? 'ogg' :
            oAudio.canPlayType('audio/mpeg;')? 'mp3' : false;
        var url = $('#elemTpls > a.sound-url').attr('href');
        if (!url) return;
        if (!codec) {
            oAudio = null; return;
        }
        oAudio.src = url.replace(/\*/g, codec);
        oAudio.load();
    }

    function playSound() {
        if (oAudio) oAudio.play();
    }

});
