(function () {
  'use strict';

  function getComputedStyle(a, b) {
    if (!a) return false;
    if (a.currentStyle) return a.currentStyle;
    if (window.getComputedStyle) return window.getComputedStyle(a, b);
    return false;
  }

  function getCoordinates(a) {
    let x = 0;
    let y = 0;
    const h = parseInt(a.offsetHeight, 10) || 0;
    const w = parseInt(a.offsetWidth, 10) || 0;
    let element = a;
    do {
      x += parseInt(element.offsetLeft, 10) || 0;
      y += parseInt(element.offsetTop, 10) || 0;
      element = element.offsetParent;
    } while (element);
    return {
      x,
      y,
      yh: y + h,
      xw: x + w,
      w,
      h,
    };
  }

  function insertBefore(a, b) {
    if (a.parentNode) a.parentNode.removeChild(a);
    b.parentNode.insertBefore(a, b);
  }

  function insertAfter(a, b) {
    if (a.parentNode) a.parentNode.removeChild(a);
    b.parentNode.insertBefore(a, b.nextSibling);
  }

  class Color {
    constructor(colorToParse) {
      let a = colorToParse;
      // RGB
      if (typeof a === 'object') this.rgb = a;
      else if (typeof a === 'string') {
        const rgbMatch = a.match(/^rgb\((\d+),\s?(\d+),\s?(\d+)\)/i);
        const hexMatch = a.match(/#?[^\da-fA-F]/);
        if (rgbMatch) {
          rgbMatch[1] = parseFloat(rgbMatch[1]);
          rgbMatch[2] = parseFloat(rgbMatch[2]);
          rgbMatch[3] = parseFloat(rgbMatch[3]);
          rgbMatch.shift();
          this.rgb = rgbMatch;
        } else if (hexMatch) {
          if (a.charAt(0) === '#') {
            a = a.substr(1);
          }
          if (a.length === 3) {
            a = a.charAt(0)
              + a.charAt(0)
              + a.charAt(1)
              + a.charAt(1)
              + a.charAt(2)
              + a.charAt(2);
          }
          if (a.length !== 6) this.rgb = [0, 0, 0];
          else {
            this.rgb = [];
            for (let x = 0; x < 3; x += 1) {
              this.rgb[x] = parseInt(a.substr(x * 2, 2), 16);
            }
          }
        }
      } else {
        this.rgb = [0, 0, 0];
      }
    }

    invert() {
      this.rgb = [255 - this.rgb[0], 255 - this.rgb[1], 255 - this.rgb[2]];
      return this;
    }

    toRGB() {
      return this.rgb;
    }

    toHex() {
      if (!this.rgb) return false;
      let tmp2;
      let tmp = '';
      let x;
      const hex = '0123456789ABCDEF';
      for (x = 0; x < 3; x += 1) {
        tmp2 = this.rgb[x];
        tmp
          += hex.charAt(Math.floor(tmp2 / 16)) + hex.charAt(Math.floor(tmp2 % 16));
      }
      return tmp;
    }
  }

  const { userAgent } = navigator;

  var Browser = {
    chrome: !!userAgent.match(/chrome/i),
    ie: !!userAgent.match(/msie/i),
    iphone: !!userAgent.match(/iphone/i),
    mobile: !!userAgent.match(/mobile/i),
    n3ds: !!userAgent.match(/nintendo 3ds/),
    firefox: !!userAgent.match(/firefox/i),
    safari: !!userAgent.match(/safari/i),
  };

  /**
   * This method adds some decoration to the default browser event.
   * This can probably be replaced with something more modern.
   */
  function Event(e) {
    const dB = document.body;
    const dE = document.documentElement;
    switch (e.keyCode) {
      case 13:
        e.ENTER = true;
        break;
      case 37:
        e.LEFT = true;
        break;
      case 38:
        e.UP = true;
        break;
      case 0.39:
        e.RIGHT = true;
        break;
      case 40:
        e.DOWN = true;
        break;
      default:
        break;
    }
    if (typeof e.srcElement === 'undefined') e.srcElement = e.target;
    if (typeof e.pageY === 'undefined') {
      e.pageY = e.clientY + (parseInt(dE.scrollTop || dB.scrollTop, 10) || 0);
      e.pageX = e.clientX + (parseInt(dE.scrollLeft || dB.scrollLeft, 10) || 0);
    }
    e.cancel = () => {
      e.returnValue = false;
      if (e.preventDefault) e.preventDefault();
      return e;
    };
    e.stopBubbling = () => {
      if (e.stopPropagation) e.stopPropagation();
      e.cancelBubble = true;
      return e;
    };
    return e;
  }

  // TODO: There are places in the source that are using this to store a callback
  // Refactor this
  Event.onPageChange = function onPageChange() {};

  /* global RUN */

  /**
   * Run a callback function either when the DOM is loaded and ready,
   * or immediately if the document is already loaded.
   * @param {Function} callback
   */
  function onDOMReady(callback) {
    if (document.readyState === 'complete') {
      callback();
    } else {
      document.addEventListener('DOMContentLoaded', callback);
    }
  }

  /**
   * For some reason, I designed this method
   * to accept Objects (key/value pairs)
   * or 2 arguments:  keys and values
   * The purpose is to construct data to send over URL or POST data
   *
   * @example
   * buildQueryString({key: 'value', key2: 'value2'}) === 'key=value&key2=value2';
   *
   * @example
   * buildQueryString(['key', 'key2'], ['value, 'value2']) === 'key=value&key2=value2'
   *
   * @return {String}
   */
  function buildQueryString(keys, values) {
    if (!keys) {
      return '';
    }
    if (values) {
      return keys
        .map((key, index) => `${encodeURIComponent(key)}=${encodeURIComponent(values[index] || '')}`)
        .join('&');
    }
    return Object.keys(keys)
      .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(keys[key] || '')}`)
      .join('&');
  }

  class Ajax {
    constructor(s) {
      this.setup = {
        readyState: 4,
        callback() {},
        method: 'POST',
        ...s,
      };
    }

    load(url, callback, data, method = this.setup.method, requestType = 1) {
      // requestType is an enum (1=update, 2=load new)
      let sendData = null;
      if (
        data
        && Array.isArray(data)
        && Array.isArray(data[0])
        && data[0].length === data[1].length
      ) {
        sendData = buildQueryString(data[0], data[1]);
      } else if (typeof data !== 'string') {
        sendData = buildQueryString(data);
      }
      const request = new XMLHttpRequest();
      if (callback) {
        this.setup.callback = callback;
      }
      request.onreadystatechange = () => {
        if (request.readyState === this.setup.readyState) {
          this.setup.callback(request);
        }
      };
      if (!request) return false;
      request.open(method, url, true);
      request.url = url;
      request.type = requestType;
      if (method) {
        request.setRequestHeader(
          'Content-Type',
          'application/x-www-form-urlencoded',
        );
      }
      request.setRequestHeader('X-JSACCESS', requestType);
      request.send(sendData);
      return request;
    }
  }

  const DISALLOWED_TAGS = [
    'SCRIPT',
    'STYLE',
    'HR',
  ];

  function htmlToBBCode(html) {
    let bbcode = html;
    bbcode = bbcode.replace(/[\r\n]+/g, '');
    bbcode = bbcode.replace(/<(hr|br|meta)[^>]*>/gi, '\n');
    bbcode = bbcode.replace(/<img.*?src=["']?([^'"]+)["'][^>]*\/?>/g, '[img]$1[/img]');
    bbcode = bbcode.replace(/<(\w+)([^>]*)>([\w\W]*?)<\/\1>/gi, (
      whole,
      tag,
      attributes,
      innerHTML,
    ) => {
      let innerhtml = innerHTML;
      const att = {};
      attributes.replace(
        /(color|size|style|href|src)=(['"]?)(.*?)\2/gi,
        (_, attr, q, value) => {
          att[attr] = value;
        },
      );
      const { style = '' } = att;

      const lcTag = tag.toLowerCase();
      if (DISALLOWED_TAGS.includes(lcTag)) {
        return '';
      }
      if (style.match(/background(-color)?:[^;]+(rgb\([^)]+\)|#\s+)/i)) {
        innerhtml = `[bgcolor=#${
        new Color(RegExp.$2).toHex()
      }]${
        innerhtml
      }[/bgcolor]`;
      }
      if (style.match(/text-align: ?(right|center|left);/i)) {
        innerhtml = `[align=${RegExp.$1}]${innerhtml}[/align]`;
      }
      if (
        style.match(/font-style: ?italic;/i)
        || lcTag === 'i'
        || lcTag === 'em'
      ) {
        innerhtml = `[I]${innerhtml}[/I]`;
      }
      if (style.match(/text-decoration:[^;]*underline;/i) || lcTag === 'u') {
        innerhtml = `[U]${innerhtml}[/U]`;
      }
      if (
        style.match(/text-decoration:[^;]*line-through;/i)
        || lcTag === 's'
      ) {
        innerhtml = `[S]${innerhtml}[/S]`;
      }
      if (
        style.match(/font-weight: ?bold;/i)
        || lcTag === 'strong'
        || lcTag === 'b'
      ) {
        innerhtml = `[B]${innerhtml}[/B]`;
      }
      if (att.size || style.match(/font-size: ?([^;]+)/i)) {
        innerhtml = `[size=${att.size || RegExp.$1}]${innerhtml}[/size]`;
      }
      if (att.color || style.match(/color: ?([^;]+)/i)) {
        innerhtml = `[color=${att.color || RegExp.$1}]${innerhtml}[/color]`;
      }
      if (lcTag === 'a' && att.href) {
        innerhtml = `[url=${att.href}]${innerhtml}[/url]`;
      }
      if (lcTag === 'ol') innerhtml = `[ol]${innerhtml}[/ol]`;
      if (lcTag === 'ul') innerhtml = `[ul]${innerhtml}[/ul]`;
      if (lcTag.match(/h\d/i)) {
        innerhtml = `[${
        lcTag
      }]${
        innerhtml
      }[/${
        lcTag
      }]`;
      }
      if (lcTag === 'li') {
        innerhtml = `*${innerhtml.replace(/[\n\r]+/, '')}\n`;
      }
      if (lcTag === 'p') {
        innerhtml = `\n${innerhtml === '&nbsp' ? '' : innerhtml}\n`;
      }
      if (lcTag === 'div') {
        innerhtml = `\n${innerhtml}`;
      }
      return innerhtml;
    });
    return bbcode
      .replace(/&gt;/g, '>')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&nbsp;/g, ' ');
  }


  function bbcodeToHTML(bbcode) {
    let html = bbcode
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/(\s) /g, '$1&nbsp;');
    html = html.replace(/\[(b|i|u|s)\]([\w\W]*?)\[\/\1\]/gi, '<$1>$2</$1>');
    html = html.replace(/\[img\]([^'"[]+)\[\/img\]/gi, '<img src="$1">');
    html = html.replace(
      /\[color=([^\]]+)\](.*?)\[\/color\]/gi,
      '<span style="color:$1">$2</span>',
    );
    html = html.replace(
      /\[size=([^\]]+)\](.*?)\[\/size\]/gi,
      '<span style="font-size:$1">$2</span>',
    );
    html = html.replace(
      /\[url=([^\]]+)\](.*?)\[\/url\]/gi,
      '<a href="$1">$2</a>',
    );
    html = html.replace(
      /\[bgcolor=([^\]]+)\](.*?)\[\/bgcolor\]/gi,
      '<span style="backgroun-color:$1">$2</span>',
    );
    html = html.replace(/\[h(\d)\](.*?)\[\/h\1\]/g, '<h$1>$2</h$1>');
    html = html.replace(
      /\[align=(left|right|center)\](.*?)\[\/align\]/g,
      '<span style="text-align:$1">$2</span>',
    );
    html = html.replace(/\[(ul|ol)\]([\w\W]*?)\[\/\1\]/gi, (match) => {
      const tag = match[1];
      const listItems = match[2].split(/([\r\n]+|^)\*/);
      const lis = listItems
        .filter(text => text.trim())
        .map(text => `<li>${text}</li>`)
        .join('');
      return `<${tag}>${lis}</${tag}>`;
    });
    html = html.replace(/\n/g, '<br />');
    return html;
  }

  /* global globalsettings */

  const URL_REGEX = /^(ht|f)tps?:\/\/[\w.\-%&?=/]+$/;
  const isURL = text => URL_REGEX.test(text);

  class Editor {
    constructor(textarea, iframe) {
      if (!iframe.timedout) {
        iframe.timedout = true;
        setTimeout(() => {
          // eslint-disable-next-line no-new
          new Editor(textarea, iframe);
        }, 100);
        return null;
      }

      if (iframe.editor) {
        return null;
      }

      this.iframe = iframe;
      iframe.editor = this;
      iframe.className = 'editorframe';
      // 1 for html editing mode, 0 for textarea mode
      this.mode = Browser.mobile || Browser.n3ds ? 0 : globalsettings.wysiwyg;
      this.mode = this.mode || 0;
      this.textarea = textarea;
      this.window = iframe.contentWindow;
      this.doc = iframe.contentWindow.document;

      const cs = getComputedStyle(this.textarea);
      const body = this.doc.getElementsByTagName('body')[0];
      if (body && cs) {
        body.style.backgroundColor = cs.backgroundColor;
        body.style.color = cs.color;
        body.style.borderColor = '#FFF';
      }

      this.doc.designMode = 'on';

      this.editbar = document.createElement('div');
      this.buildEditBar();

      this.editbar.style.width = `${textarea.clientWidth + 2}px`;
      iframe.style.width = `${textarea.clientWidth}px`;
      iframe.style.height = `${textarea.clientHeight}px`;

      insertBefore(this.editbar, textarea);

      // Set the source and initialize the editor
      //
      this.setSource('<div></div>');
      setTimeout(() => {
        this.setSource(bbcodeToHTML(textarea.value));
        this.switchMode(this.mode);
      }, 100);
      return this;
    }

    buildEditBar() {
      this.editbar.className = 'editbar';
      const cmds = [
        'bold',
        'italic',
        'underline',
        'strikethrough',
        'forecolor',
        'backcolor',
        'insertimage',
        'createlink',
        'c_email',
        'justifyleft',
        'justifycenter',
        'justifyright',
        'c_youtube',
        'c_code',
        'c_quote',
        'c_spoiler',
        'insertorderedlist',
        'insertunorderedlist',
        'c_smileys',
        'c_switcheditmode',
      ];

      const cmddesc = [
        'Bold',
        'Italic',
        'Underline',
        'Strike-Through',
        'Foreground Color',
        'Background Color',
        'Insert Image',
        'Insert Link',
        'Insert email',
        'Align left',
        'Center',
        'Align right',
        'Insert video from any of your favorite video services!',
        'Insert code',
        'Insert Quote',
        'Insert Spoiler',
        'Create Ordered List',
        'Create Unordered List',
        'Insert Emoticon',
        'Switch editor mode',
      ];

      cmds.forEach((cmd, i) => {
        const a = document.createElement('a');
        a.className = cmd;
        a.title = cmddesc[i];
        a.href = 'javascript:void(0)';
        a.unselectable = 'on';
        a.onclick = event => this.editbarCommand(event, this.className);
        this.editbar.appendChild(a);
      });
    }

    editbarCommand(event, cmd) {
      const e = Event(event).cancel();

      switch (cmd) {
        case 'forecolor':
        case 'backcolor':
          this.showColors(e.pageX, e.pageY, cmd);
          break;
        case 'c_smileys':
          this.showEmotes(e.pageX, e.pageY);
          break;
        case 'c_switcheditmode':
          this.switchMode(Math.abs(this.mode - 1));
          break;
        default:
          this.cmd(cmd);
          break;
      }
    }

    showEmotes(x, y) {
      const emotewin = this.emoteWindow;
      if (!emotewin) {
        this.createEmoteWindow.x = x;
        this.createEmoteWindow.y = y;
        new Ajax().load('/misc/emotes.php?json', this.createEmoteWindow);
        return;
      }
      if (emotewin.style.display === 'none') {
        emotewin.style.display = '';
        emotewin.style.top = `${y}px`;
        emotewin.style.left = `${x}px`;
      } else {
        this.hideEmotes();
      }
    }

    hideEmotes() {
      if (this.emoteWindow) {
        this.emoteWindow.style.display = 'none';
      }
    }

    createEmoteWindow(xml) {
      const smilies = JSON.parse(xml.responseText);
      const emotewin = document.createElement('div');
      emotewin.className = 'emotewin';

      smilies.forEach((smiley, i) => {
        const r = document.createElement('a');
        r.href = 'javascript:void(0)';
        r.emotetext = smilies[0][i];
        r.onclick = () => {
          this.cmd('inserthtml', this.emotetext);
          this.hideEmotes();
        };
        r.innerHTML = `${smilies[1][i]} ${smilies[0][i]}`;
        emotewin.appendChild(r);
      });

      emotewin.style.position = 'absolute';
      emotewin.style.display = 'none';
      this.emoteWindow = emotewin;
      document.querySelector('#page').appendChild(emotewin);
      this.showEmotes(this.createEmoteWindow.x, this.createEmoteWindow.y);
    }

    colorHandler(cmd) {
      this.cmd(cmd, this.style.backgroundColor);
      this.hideColors();
    }

    showColors(posx, posy, cmd) {
      if (this.colorWindow && this.colorWindow.style.display !== 'none') {
        return this.hideColors();
      }
      let colorwin = this.colorWindow;
      const colors = [
        'FFFFFF',
        'AAAAAA',
        '000000',
        'FF0000',
        '00FF00',
        '0000FF',
        'FFFF00',
        '00FFFF',
        'FF00FF',
      ];
      const l = colors.length;
      const sq = Math.ceil(Math.sqrt(l));
      let r;
      let c;
      let a;
      if (!colorwin) {
        colorwin = document.createElement('table');
        colorwin.style.borderCollapse = 'collapse';
        colorwin.style.position = 'absolute';
        for (let y = 0; y < sq; y += 1) {
          r = colorwin.insertRow(y);
          for (let x = 0; x < sq; x += 1) {
            c = r.insertCell(x);
            if (!colors[x + y * sq]) {
              // eslint-disable-next-line no-continue
              continue;
            }
            c.style.border = '1px solid #000';
            c.style.padding = 0;
            a = document.createElement('a');
            a.href = 'javascript:void(0)';
            a.onclick = () => this.colorHandler(cmd);
            c.appendChild(a);
            c = a.style;
            c.display = 'block';
            c.backgroundColor = `#${colors[x + y * sq]}`;
            c.height = '20px';
            c.width = '20px';
            c.margin = 0;
          }
        }
        this.colorWindow = colorwin;
        document.querySelector('#page').appendChild(colorwin);
      } else {
        colorwin.style.display = '';
      }
      colorwin.style.top = `${posy}px`;
      colorwin.style.left = `${posx}px`;
      return null;
    }

    hideColors() {
      if (this.colorWindow) {
        this.colorWindow.style.display = 'none';
      }
    }

    cmd(command, arg) {
      let rng;
      const selection = this.getSelection();
      let bbcode;
      let realCommand = command;
      let arg1 = arg;
      switch (command.toLowerCase()) {
        case 'bold':
          bbcode = `[b]${selection}[/b]`;
          break;
        case 'italic':
          bbcode = `[i]${selection}[/i]`;
          break;
        case 'underline':
          bbcode = `[u]${selection}[/u]`;
          break;
        case 'strikethrough':
          bbcode = `[s]${selection}[/s]`;
          break;
        case 'justifyright':
          bbcode = `[align=right]${selection}[/align]`;
          break;
        case 'justifycenter':
          bbcode = `[align=center]${selection}[/align]`;
          break;
        case 'justifyleft':
          bbcode = `[align=left]${selection}[/align]`;
          break;
        case 'insertimage':
          arg1 = prompt('Image URL:');
          if (!arg1) {
            return;
          }
          if (!isURL(arg1)) {
            alert('Please enter a valid URL.');
            return;
          }
          bbcode = `[img]${arg1}[/img]`;
          break;
        case 'insertorderedlist':
          if (!this.mode) {
            bbcode = `[ol]${selection.replace(/(.+([\r\n]+|$))/gi, '*$1')}[/ol]`;
          }
          break;
        case 'insertunorderedlist':
          if (!this.mode) {
            bbcode = `[ul]${selection.replace(/(.+([\r\n]+|$))/gi, '*$1')}[/ul]`;
          }
          break;
        case 'createlink':
          arg1 = prompt('Link:');
          if (!arg1) return;
          if (!arg1.match(/^(https?|ftp|mailto):/)) arg1 = `https://${arg1}`;
          bbcode = `[url=${arg1}]${selection}[/url]`;
          break;
        case 'c_email':
          arg1 = prompt('Email:');
          if (!arg1) return;
          realCommand = 'createlink';
          arg1 = `mailto:${arg1}`;
          bbcode = `[url=${arg1}]${selection}[/url]`;
          break;
        case 'backcolor':
          if (Browser.firefox || Browser.safari) {
            realCommand = 'hilitecolor';
          }
          bbcode = `[bgcolor=${arg1}]${selection}[/bgcolor]`;
          break;
        case 'forecolor':
          bbcode = `[color=${arg1}]${selection}[/color]`;
          break;
        case 'c_code':
          realCommand = 'inserthtml';
          arg1 = `[code]${selection}[/code]`;
          bbcode = arg1;
          break;
        case 'c_quote':
          realCommand = 'inserthtml';
          arg1 = prompt('Who said this?');
          arg1 = `[quote${arg1 ? `=${arg1}` : ''}]${selection}[/quote]`;
          bbcode = arg1;
          break;
        case 'c_spoiler':
          realCommand = 'inserthtml';
          arg1 = `[spoiler]${selection}[/spoiler]`;
          bbcode = arg1;
          break;
        case 'c_youtube':
          realCommand = 'inserthtml';
          arg1 = prompt('Video URL?');
          if (!arg1) {
            return;
          }
          arg1 = `[video]${arg1}[/video]`;
          bbcode = arg1;
          break;
        case 'inserthtml':
          bbcode = arg1;
          break;
        default:
          throw new Error(`Unsupported editor command ${command}`);
      }
      if (this.mode) {
        if (realCommand === 'inserthtml' && Browser.ie) {
          rng = this.doc.selection.createRange();
          if (!rng.text.length) this.doc.body.innerHTML += arg1;
          else {
            rng.pasteHTML(arg1);
            rng.collapse(false);
            rng.select();
          }
        } else {
          this.doc.execCommand(realCommand, false, arg1 || false);
          if (this.iframe.contentWindow.focus) {
            this.iframe.contentWindow.focus();
          }
        }
      } else Editor.setSelection(this.textarea, bbcode);
    }

    getSelection() {
      if (this.mode) {
        return Browser.ie
          ? this.doc.selection.createRange().text
          : this.window.getSelection();
      }
      if (Browser.ie) {
        this.textarea.focus();
        return document.selection.createRange().text;
      }
      return this.textarea.value.substring(
        this.textarea.selectionStart,
        this.textarea.selectionEnd,
      );
    }

    getSource() {
      return this.doc.body.innerHTML;
    }

    setSource(a) {
      if (this.doc && this.doc.body) this.doc.body.innerHTML = a;
    }

    switchMode(toggle) {
      const t = this.textarea;
      const f = this.iframe;
      if (!toggle) {
        t.value = htmlToBBCode(this.getSource());
        t.style.display = '';
        f.style.display = 'none';
      } else {
        this.setSource(bbcodeToHTML(t.value));
        t.style.display = 'none';
        f.style.display = '';
      }
      this.mode = toggle;
    }

    submit() {
      if (this.mode) {
        this.switchMode(0);
        this.switchMode(1);
      }
    }
  }

  Editor.setSelection = function setSelection(t, stuff) {
    const scroll = t.scrollTop;
    if (Browser.ie) {
      t.focus();
      document.selection.createRange().text = stuff;
    } else {
      const s = t.selectionStart;
      const e = t.selectionEnd;
      t.value = t.value.substring(0, s) + stuff + t.value.substr(e);
      t.selectionStart = s + stuff.length;
      t.selectionEnd = s + stuff.length;
    }
    t.focus();
    t.scrollTop = scroll;
  };

  // TODO: make this not global
  window.dropdownMenu = function dropdownMenu(e) {
    const el$$1 = e.srcElement || e.target;
    if (el$$1.tagName.toLowerCase() === 'a') {
      const menu = document.querySelector(`#menu_${el$$1.classList[0]}`);
      el$$1.classList.add('active');
      const s = menu.style;
      s.display = 'block';
      const p = getCoordinates(el$$1);
      s.top = `${p.y + el$$1.clientHeight}px`;
      s.left = `${p.x}px`;
      el$$1.onmouseout = (e2) => {
        if (!e2.relatedTarget && e2.toElement) e2.relatedTarget = e2.toElement;
        if (e2.relatedTarget !== menu && e2.relatedTarget.offsetParent !== menu) {
          el$$1.classList.remove('active');
          menu.style.display = 'none';
        }
      };
      menu.onmouseout = (e2) => {
        if (!e2.relatedTarget && e2.toElement) e2.relatedTarget = e2.toElement;
        if (
          e2.relatedTarget !== el$$1
          && e2.relatedTarget.offsetParent !== menu
          && e2.relatedTarget !== menu
        ) {
          el$$1.classList.remove('active');
          menu.style.display = 'none';
        }
      };
    }
  };

  function makestuffcool() {
    const switches = Array.from(document.querySelectorAll('.switch'));
    switches.forEach((switchEl) => {
      const t = document.createElement('div');
      t.className = switchEl.className.replace('switchEl', 'switchEl_converted');
      t.s = switchEl;
      switchEl.t = t;
      switchEl.style.display = 'none';
      if (!switchEl.checked) t.style.backgroundPosition = 'bottom';
      const set = (onoff = switchEl.checked) => {
        switchEl.checked = onoff;
        switchEl.t.style.backgroundPosition = switchEl.checked ? 'top' : 'bottom';
        if (switchEl.onchange) switchEl.onchange();
      };
      t.onclick = () => set();
      insertAfter(t, switchEl);
    });

    const editor = document.querySelector('.editor');
    if (editor) {
      editor.onkeydown = (event) => {
        if (event.keyCode === 9) {
          Editor.setSelection(editor, '    ');
          return false;
        }
        return true;
      };
    }
  }
  onDOMReady(makestuffcool);

  window.submitForm = function submitForm(a) {
    const names = [];
    const values = [];
    let x;
    const elements = Array.from(a.elements);
    const submit = a.submitButton;
    elements.forEach((element) => {
      if (!element.name || element.type === 'submit') return;
      if ((element.type === 'checkbox' || a[x].type === 'radio') && !a[x].checked) {
        return;
      }
      names.push(a[x].name);
      values.push(a[x].value);
    });

    if (submit) {
      names.push(submit.name);
      values.push(submit.value);
    }
    new Ajax().load(document.location.search, 0, [names, values], 1, 1);
    // eslint-disable-next-line no-alert
    alert("Saved. Ajax-submitted so you don't lose your place");
    return false;
  };

}());
