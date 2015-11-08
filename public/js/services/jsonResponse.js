var jsonResponse = angular.module('jsonResponse', []);

jsonResponse.factory('jsonResponse', [
  '$http', '$rootScope',
  function ($http, $rootScope) {
    var jsonResponse = {};

    function notify (severity, message) {
    if(severity == "error")
        severity = "danger";
      $.bootstrapGrowl(message,{'type':severity,delay:3000,width:'auto',align:'center'});
    }

    jsonResponse.httpErrorHandler = function () {
      console.log(this);
    };

    jsonResponse.defaultCallback = function () {};

    jsonResponse.post = function (url, data, callback) {
      if (callback === undefined) {
        callback = this.defaultCallback;
      }
      var promise = $http.post(url, data);
      promise.error(this.httpErrorHandler);
      promise.success(function(response, status, headers, config){
        if (status == 200) {
          if('message' in response) {
            notify(response.message.type, response.message.text);
          }
          if('result' in response) {
            callback(response.result);
          } else {
            callback();
          }
        } else {
          notify("danger", "Failed retrieving data from server");
          console.log(status + response);
        }
      });
    };

    jsonResponse.get = function (url, callback) {
      if (callback === undefined) {
        callback = this.defaultCallback;
      }
      var promise = $http.get(url);
      promise.error(this.httpErrorHandler);
      promise.success(function(response, status, headers, config){
        if (status == 200) {
          if('message' in response) {
            notify(response.message.text, {}, response.message.type);
          }
          if('result' in response) {
            callback(response.result);
          } else {
            callback();
          }
        } else {
          notify("danger", "Failed retrieving data from server");
          console.log(status + response);
        }
      });
    };

    return jsonResponse;
  }
]);
