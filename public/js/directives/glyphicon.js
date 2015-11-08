app.directive('glyphicon', function () {
  return {
    template: '<span class="glyphicon glyphicon-{{name}}"></span>',
    restrict: 'E',
    scope: {
    },
    link: function (scope, element, attrs) {
      scope.name = attrs.name;
    }
  };
});
