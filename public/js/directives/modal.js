app.directive('modal', function () {
  return {
    templateUrl: 'partials/modal.html',
    restrict: 'E',
    transclude: true,
    replace: true,
    scope: {
      'bindTitle': '=',
      'visible': '='
    },
    link: function (scope, element, attrs) {
      scope.title = attrs.title;

      scope.$watch('visible', function(value){
        if(value == true)
          $(element).modal('show');
        else
          $(element).modal('hide');
      });

      if (attrs.bindTitle) {
        scope.$watch('bindTitle', function (value){
          scope.title = value;
        });
      }

      $(element).on('shown.bs.modal', function(){
        scope.$apply(function(){
          scope.$parent[attrs.visible] = true;
        });
      });

      $(element).on('hidden.bs.modal', function(){
        scope.$apply(function(){
          scope.$parent[attrs.visible] = false;
        });
      });

      scope.$on('$destroy', function() {
        $(element).modal('hide');
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
      });
    }
  };
});
