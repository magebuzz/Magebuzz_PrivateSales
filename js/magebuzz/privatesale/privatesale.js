var DealTimeCounter = Class.create();
DealTimeCounter.prototype = {
  //params now_time, end_time : seconds
  initialize: function (now_time, end_time, deal_id) {
    this.now_time = parseInt(now_time) * 1000;
    this.end_time = parseInt(end_time) * 1000;
    this.deal_id = deal_id;
    this.end = new Date(this.end_time);
    var endDate = this.end;
    this.second = endDate.getSeconds();
    this.minute = endDate.getMinutes();
    this.hour = endDate.getHours();
    this.day = endDate.getDate();
    this.month = endDate.getMonth();
    var yr;
    if (endDate.getYear() < 1900)
      yr = endDate.getYear() + 1900;
    else
      yr = endDate.getYear();
    this.year = yr;
  },

  setTimeleft: function (timeleft_id) {
    var now = new Date(this.now_time);
    var yr;

    if (now.getYear() < 1900)
      yr = now.getYear() + 1900;
    else
      yr = now.getYear();

    var endtext = '0';
    var timerID;

    var sec = this.second - now.getSeconds();

    var min = this.minute - now.getMinutes();
    var hr = this.hour - now.getHours();
    var dy = this.day - now.getDate();
    var mnth = this.month - now.getMonth();
    yr = this.year - yr;

    var daysinmnth = 32 - new Date(now.getYear(), now.getMonth(), 32).getDate();
    if (sec < 0) {
      sec = (sec + 60) % 60;
      min--;
    }
    if (min < 0) {
      min = (min + 60) % 60;
      hr--;
    }
    if (hr < 0) {
      hr = (hr + 24) % 24;
      dy--;
    }
    if (dy < 0) {
      dy = (dy + daysinmnth) % daysinmnth;
      mnth--;
    }
    if (mnth < 0) {
      mnth = (mnth + 12) % 12;
      yr--;
    }
    var sectext = "";
    var mintext = ":";
    var hrtext = ":";
    var dytext = " days, ";
    var mnthtext = " months, ";
    var yrtext = " years, ";
    if (yr == 1)
      yrtext = " year, ";
    if (mnth == 1)
      mnthtext = " month, ";
    if (dy == 1)
      dytext = " day, ";
    if (hr == 1)
      hrtext = ":";
    if (min == 1)
      mintext = ":";
    if (sec == 1)
      sectext = "";

    if (dy < 10)
      dy = '0' + dy;
    if (hr < 10)
      hr = '0' + hr;
    if (min < 10)
      min = '0' + min;
    if (sec < 10)
      sec = '0' + sec;

    if (yr <= 0)
      yrtext = ''
    else
      yrtext = yr + yrtext;

    if ((mnth <= 0))
      mnthtext = ''
    else
      mnthtext = '<span class="timeleft-text">' + mnth + '</span>' + mnthtext;

    if (dy <= 0 && mnth > 0)
      dytext = ''
    else
      dytext = '<span class="timeleft-text">' + dy + '</span>' + dytext;

    if (hr <= 0 && dy > 0)
      hrtext = ''
    else
      hrtext = '<span class="timeleft-text">' + hr + '</span>' + hrtext;

    if (min < 0)
      mintext = ''
    else
      mintext = '<span class="timeleft-text">' + min + '</span>' + mintext;

    if (sec < 0)
      sectext = ''
    else
      sectext = '<span class="timeleft-text">' + sec + '</span>' + sectext;

    if (now >= this.end) {
      document.getElementById(timeleft_id).innerHTML = endtext;
      clearTimeout(timerID);
    }
    else {

      document.getElementById(timeleft_id).innerHTML = yrtext + mnthtext + dytext + hrtext + mintext + sectext;
    }

    if (this.now_time == this.end_time) {
      location.reload(true);
      return;
    }

    this.now_time = this.now_time + 1000; //incres 1000 miliseconds

    timerID = setTimeout("setDealTimeleft(" + (this.now_time / 1000) + "," + (this.end_time / 1000) + ",'" + timeleft_id + "','" + this.deal_id + "');", 1000);
  }

}

function setDealTimeleft(now_time, end_time, timeleft_id, deal_id) {
  var counter = new DealTimeCounter(now_time, end_time, deal_id);
  counter.setTimeleft(timeleft_id);
}

function myPopupRelocate(element_id) {
  var scrolledX, scrolledY;
  if (self.pageYOffset) {
    scrolledX = self.pageXOffset;
    scrolledY = self.pageYOffset;
  } else if (document.documentElement && document.documentElement.scrollTop) {
    scrolledX = document.documentElement.scrollLeft;
    scrolledY = document.documentElement.scrollTop;
  } else if (document.body) {
    scrolledX = document.body.scrollLeft;
    scrolledY = document.body.scrollTop;
  }

  var centerX, centerY;
  if (self.innerHeight) {
    centerX = self.innerWidth;
    centerY = self.innerHeight;
  } else if (document.documentElement && document.documentElement.clientHeight) {
    centerX = document.documentElement.clientWidth;
    centerY = document.documentElement.clientHeight;
  } else if (document.body) {
    centerX = document.body.clientWidth;
    centerY = document.body.clientHeight;
  }

  var leftOffset = scrolledX + (centerX - 250) / 2;
  var topOffset = scrolledY + (centerY - 200) / 2;

  document.getElementById(element_id).style.top = topOffset + "px";
  document.getElementById(element_id).style.left = leftOffset + "px";
}

function fireMyPopup(element_id) {
  myPopupRelocate(element_id);
  document.getElementById(element_id).style.display = "block";
  document.body.onscroll = myPopupRelocate(element_id);
  window.onscroll = myPopupRelocate(element_id);
}

function close_popup(element) {
  $('subscribe-result-message').update('');
  $('dailydeal-subscription-form').show();
  $('dailydeal_email').value = '';
  $(element).hide();
}

function close_popup_message(element) {
  $(element).hide();
}

function submit_dailydeal_newsletter(newsletter_url) {
  var parameters = {
    email_address: $('dailydeal_email').value
  };
  //alert (parameters.email_address);
  show_loading(true);
  var request = new Ajax.Request(
    newsletter_url,
    {
      method: 'post',
      onSuccess: function (transport) {
        var data = transport.responseText.evalJSON();
        if (data.error) {
          show_loading(false);
          $('subscribe-result-message').update(data.message);
        }
        else {
          show_loading(false);
          $('subscribe-result-message').update(data.message);
          //sleep(2000);
          //setTimeout($('subscribe-result-message'), 10000);
        }
      },
      parameters: parameters,
    }
  );
}

function show_loading(is_show) {
  if (is_show) {
    $('dailydeal-subscription-form').hide();
    $('subscribe-form-ajax').show();
  }
  else {
    //$('dailydeal-subscription-form').show();
    $('subscribe-form-ajax').hide();
  }
}

var Deal = Class.create();
Deal.prototype = {
  initialize: function (changeProductUrl) {

    this.changeProductUrl = changeProductUrl;

  },

  changeProduct: function (product_id) {
    var url = this.changeProductUrl;

    url += 'product_id/' + product_id;
    new Ajax.Updater(
      'product_name_contain',
      url,
      {
        method: 'get',
        onComplete: function () {
          $('product_name').value = $('newproduct_name').value;
          $('product_price').value = $('newproduct_price').value;
          $('product_quantity').value = $('newproduct_quantity').value;
        },
        onFailure: ''
      }
    );

  }
}

function updateProductName() {
  alert('hehe');
  $('product_name').value = $('newproduct_name').value;
}

// update version 1.4

Privatesale = Class.create();
Privatesale.prototype = {
  optionConfig: null,
  productids: null,
  initialize: function (optionConfig, productids) {

    this.optionConfig = optionConfig;
    this.productids = productids;
  },
  replaceButtonAddToCart: function (optionConfig, productids) {
    var addCartClass = 'button.btn-cart';
    var boxPrice = 'div.price-box';
    var idProduct = '';
    if (optionConfig == 2) {
      $$(addCartClass).each(function (elementAdd) {
        var el = $(elementAdd.parentNode.parentNode);
        if (el) {
          var idProduct = this.searchPriceBox(el, elementAdd, productids);
        }
        if (idProduct == '') {
          var el = $(elementAdd.parentNode.parentNode.parentNode);
          if (el) {
            var idProduct = this.searchPriceBox(el, elementAdd, productids);
          }
        }
      }.bind(this));
    } else if (optionConfig == 1) {
      $$(boxPrice).each(function (elementPrice) {
        var childNext = elementPrice.childElements()[0];
        if (childNext) {
          if (childNext.hasClassName('regular-price')) {
            idProduct = childNext.id.replace(/[^\d]/gi, '');
          } else {
            childNext.childElements().each(function (childNext) {
              idProduct = childNext.id.replace(/[a-z-]*/, '');
            }.bind(this));

          }
        }
        if (parseInt(idProduct) > 0) {
          var tmp = parseInt(idProduct);
          var index = productids.indexOf(tmp);
          if (index >= 0) {
            elementPrice.remove();
          }
        }
        else {
          idProduct = '';
        }
      }.bind(this));
    }

  },
  searchPriceBox: function (parent, element, productids) {

    var child = parent.getElementsByClassName('price-box')[0];
    if (child) {
      var childNext = child.childElements()[0];
      if (childNext) {
        if (childNext.hasClassName('regular-price')) {
          idProduct = childNext.id.replace(/[^\d]/gi, '');

        } else {
          child.childElements()[0].childElements().each(function (childNext) {
            idProduct = childNext.id.replace(/[a-z-]*/, '');
          }.bind(this));

        }
      }

      if (!idProduct && idProduct != '') {
        child.childElements()[0].childElements().each(function (childNext) {
          idProduct = childNext.id.replace(/[a-z-]*/, '');
          if (parseInt(idProduct) > 0) {
            var tmp = parseInt(idProduct);
            return idProduct;
          }
        }.bind(this));
      }
      if (parseInt(idProduct) > 0) {
        var tmp = parseInt(idProduct);
        var index = productids.indexOf(tmp);
        if (index >= 0) {
          element.remove();
        }
        return idProduct;
      }
      else {
        idProduct = '';
      }
    }
    return '';
  },
}