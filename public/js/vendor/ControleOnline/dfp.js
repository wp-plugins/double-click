var googletag = googletag || {};
googletag.cmd = googletag.cmd || [];

var DFP = {
    interv: null,
    countAds: 0,
    __construct: (function () {
        document.addEventListener("DOMContentLoaded", function () {
            DFP.run();
        });
    })(),
    removeBanners: function () {
        var remove = window.getElementsByClassName('remove-dfp');
        for (var i = 0; i < remove.length; ++i) {
            var r = remove[i];
            r.parentNode.removeChild(r);
        }
    },
    run: function () {
        var gads = document.createElement('script');
        gads.async = true;
        gads.type = 'text/javascript';
        var useSSL = 'https:' == document.location.protocol;
        gads.src = (useSSL ? 'https:' : 'http:') + '//www.googletagservices.com/tag/js/gpt.js';
        document.head.appendChild(gads);
        googletag.cmd.push(function () {
            googletag.pubads().enableSingleRequest();
            googletag.enableServices();
        });
        this.replaceBanners();
        this.detectAdBlock();
    },
    addUrlParam: function (search, key, val) {
        var newParam = key + '=' + val, params = '?' + newParam;
        if (search) {
            params = search.replace(new RegExp('[\?&]' + key + '[^&]*'), '$1' + newParam);
            if (params === search) {
                params += '&' + newParam;
            }
        }
        return params;
    },
    checkSize: function (dfp_id, iframe, innerDoc) {
        var container = innerDoc.getElementById(dfp_id + '_ad_container');
        if (container) {
            var style, o = container.getElementsByTagName('embed')[0];
            if (!o) {
                style = 'top:0;left:0;position:fixed;z-index:-999999999;width:0px;height:0px;background-color:transparent !important;';
                clearInterval(DFP.interv);
            } else {
                var w = o.offsetWidth, h = o.offsetHeight;
                if (w == 0 && h == 0) {
                    style = 'top:0;left:0;position:fixed;z-index:-999999999;width:0px;height:0px;background-color:transparent !important;';
                    clearInterval(DFP.interv);
                } else {
                    iframe.contentWindow.scrollTo(o.offsetWidth + w, o.offsetHeight + h);
                    style = 'width: ' + w + 'px;' +
                            'height: ' + h + 'px;' +
                            'z-index:999999999; ' +
                            'position: fixed; ' +
                            'top: 50%;' +
                            'left: 50%;' +
                            'margin-left: -' + (w / 2) + 'px;' +
                            'margin-top: -' + (h / 2) + 'px;' +
                            'border: 0px !important;' +
                            'background-color:transparent !important;';
                }
            }
            iframe.style.cssText = style;
        }
    },
    adaptSize: function (dfp_id) {
        var iframe = document.getElementById('iframe-' + dfp_id);
        var innerDoc = iframe.contentDocument || iframe.contentWindow.document;
        innerDoc.addEventListener("DOMSubtreeModified", function () {
            DFP.checkSize(dfp_id, iframe, innerDoc);
        });
        this.interv = setInterval(function () {
            DFP.checkSize(dfp_id, iframe, innerDoc);
        }, 500);
    },
    show: function (slot, size, dfp_id, min_width, max_width) {
        var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
        var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);
        if (w >= min_width && (max_width === '0' || w <= max_width)) {
            if (size[0] <= w && size[1] <= h) {
                this.countAds++;
                if (size[0] > 1 && size[1] > 1) {
                    googletag.cmd.push(function () {
                        googletag.defineSlot(slot, [size[0], size[1]], dfp_id).addService(googletag.pubads());
                        googletag.display(dfp_id);
                    });
                } else {
                    var iframe = document.createElement('iframe');
                    iframe.style.cssText = 'background-color:transparent!important;z-index:-999999999;height: 100%; position: fixed; top: 0; left: 0; width: 100%; border: 0px !important';
                    iframe.setAttribute('id', 'iframe-' + dfp_id);
                    iframe.setAttribute('scrolling', 'no');
                    document.getElementById(dfp_id).appendChild(iframe);
                    iframe.contentWindow.document.open();
                    iframe.contentWindow.document.write(this.iframe(slot, dfp_id));
                    iframe.contentWindow.document.close();
                    this.adaptSize(dfp_id);
                }
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    },
    iframe: function (slot, dfp_id) {
        var iframe = '<!DOCTYPE HTML>' +
                '<html lang="en-us">' +
                '<head>' +
                '<meta http-equiv="Content-type" content="text/html; charset=utf-8">' +
                '<title>' + document.title + '</title>' +
                '<style type="text/css" media="screen">' +
                '</style>' +
                '<script type="text/javascript">' +
                '(function() {' +
                'var useSSL = "https:" == document.location.protocol;' +
                'var src = (useSSL ? "https:" : "http:") + "//www.googletagservices.com/tag/js/gpt.js";' +
                'document.write("<scr" + "ipt src=\\"" + src + "\\"></scr" + "ipt>");' +
                '})();' +
                '</script>' +
                '<script type="text/javascript">' +
                'googletag.cmd.push(function() {' +
                'googletag.pubads().enableSingleRequest();' +
                'googletag.pubads().enableSyncRendering();' +
                'googletag.enableServices();' +
                '});' +
                '</script>' +
                '</head>' +
                '<body>' +
                '<!-- ' + slot + ' -->' +
                '<div id="' + dfp_id + '">' +
                '<script type="text/javascript">' +
                'googletag.cmd.push(function() {' +
                'googletag.defineOutOfPageSlot("' + slot + '", "' + dfp_id + '").addService(googletag.pubads());' +
                'googletag.display("' + dfp_id + '");' +
                '});' +
                '</script>' +
                '</div>' +
                '</body>';
        return iframe;
    },
    replaceBanners: function () {
        var banners = document.getElementsByClassName('dfp');
        for (var i = 0; i < banners.length; ++i) {
            var d = banners[i];
            var b = this.show(d.getAttribute('data-slot'), JSON.parse(d.getAttribute('data-size')), d.getAttribute('id'), d.getAttribute('min-width'), d.getAttribute('max-width'));
            if (!b) {
                d.parentNode.classList.add('remove-dfp');
            }
        }
    },
    detectAdBlock: function () {
        var s = document.createElement('script');
        s.async = true;
        s.type = 'text/javascript';
        s.innerHTML = 'setTimeout(function () {' +
                'if (!googletag.impl) {' +
                'var xmlhttp;' +
                'xmlhttp = new XMLHttpRequest();' +
                'xmlhttp.open("GET", "' +
                document.location.pathname +
                this.addUrlParam(document.location.search, 'adb', this.countAds || '0') +
                '&r="' + '+encodeURIComponent(document.location.pathname)+"' +
                '&_="' + '+(new Date().getTime() / 1000), true);' +
                'xmlhttp.send();' +
                'for (var i = 0; i < this.countAds; ++i) {' +
                'if (typeof ga !== \'undefined\') {' +
                'ga(\'send\', \'event\', \'Adblock\', \'Yes\', {\'nonInteraction\': 1});' +
                '} else if (typeof _gaq !== \'undefined\') {' +
                '_gaq.push([\'_trackEvent\', \'Adblock\', \'Yes\', undefined, undefined, true]);' +
                '}' +
                '}' +
                '}' +
                '}, 2000);';
        document.head.appendChild(s);
    }
};