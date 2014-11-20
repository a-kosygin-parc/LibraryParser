/**
 * Инициализация общего для всех скопа.
 * Бутстрапим сведения об авторизованном пользователе. Может ещё что-то
 * @param $scope
 * @constructor
 */
function InitCtrl($scope) {
	for (var i in bootstrap) {
		$scope[i] = bootstrap[i];
	}
}

/**
 * Меню навигации
 * @param $scope
 * @constructor
 */
function NavigationCtrl($scope) {
	$scope.login = function(){
		alert('login');
		//<!-- /site/login -->
	}
	$scope.logout = function(){
		alert('logout');
		//<!-- /site/logout 'linkOptions' => ['data-method' => 'post']], -->
	}
}

function Url($scope) {
	debugger;
	$http.location.href = href;
}