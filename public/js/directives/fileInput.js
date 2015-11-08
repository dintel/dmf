app.directive('fileInput', [function () {
  return {
    templateUrl: 'partials/fileInput.html',
    restrict: 'E',
    replace: true,
    scope: {
      value: '='
    },
    link: function (scope, element, attrs) {
      scope.reader = new FileReader();
      scope.reader.onload = function(file) {
        scope.value.data = file.target.result;
      };

      element.on('change', function (e) {
        var file = e.target.files[0];
        scope.reader.readAsDataURL(file);
      });
    }
}}]);
