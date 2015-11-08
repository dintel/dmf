app.directive('datetimepicker', function () {
  return {
    templateUrl: 'partials/datetimepicker.html',
    restrict: 'E',
    replace: false,
    scope: {
      value: '='
    },
    link: function (scope, element, attrs) {
      scope.title = attrs.title;

      var applyChange = true;

      $(element).datetimepicker({
        format: attrs.format,
        viewMode: attrs.viewMode,
        ignoreReadonly: true
      }).on('click', function(){
        $(this).data("DateTimePicker").show();
      });
      if (scope.value !== undefined) {
        $(element).data("DateTimePicker").date(scope.value);
      }

      $(element).on('dp.change', function(){
        var newDate = $(element).data('DateTimePicker').date().format('MMMM YYYY');
        if (applyChange)
          scope.$apply(function(){scope.value = newDate;});
      });

      scope.$watch('value', function(newVal, oldVal){
        if (newVal != oldVal) {
          applyChange = false;
          $(element).data('DateTimePicker').date(newVal);
          applyChange = true;
        }
      });
    }
  };
});
