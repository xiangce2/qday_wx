<?php defined('IN_IA') or exit('Access Denied');?><?php (!empty($this) && $this instanceof WeModuleSite || 0) ? (include $this->template('common/header-gw', TEMPLATE_INCLUDEPATH)) : (include template('common/header-gw', TEMPLATE_INCLUDEPATH));?>
<?php (!empty($this) && $this instanceof WeModuleSite || 0) ? (include $this->template('extension/service-tabs', TEMPLATE_INCLUDEPATH)) : (include template('extension/service-tabs', TEMPLATE_INCLUDEPATH));?>
<style type="text/css">
	.help-block em{display:inline-block;width:10em;font-weight:bold;font-style:normal;}
	.panel>.list-group .list-group-item {margin-left:0px; margin-right:0;}
</style>
<script>
require(['angular.sanitize', 'bootstrap', 'underscore', 'util'], function(angular, $, _, util){
	angular.module('app', ['ngSanitize']).controller('replyForm', function($scope, $http){
		$scope.reply = {
			advSetting: false,
			advTrigger: false,
			entry: <?php  echo json_encode($reply)?>
		};
		$scope.trigger = {};
		$scope.trigger.descriptions = {};
		$scope.trigger.descriptions.contains = '用户进行交谈时，对话中包含上述关键字就执行这条规则。';
		$scope.trigger.descriptions.regexp = '用户进行交谈时，对话内容符合述关键字中定义的模式才会执行这条规则。<br/><strong>注意：如果你不明白正则表达式的工作方式，请不要使用正则匹配</strong> <br/><strong>注意：正则匹配使用MySQL的匹配引擎，请使用MySQL的正则语法</strong> <br /><strong>示例: </strong><br/><em>^微信</em>匹配以“微信”开头的语句<br /><em>微信$</em>匹配以“微信”结尾的语句<br /><em>^微信$</em>匹配等同“微信”的语句<br /><em>微信</em>匹配包含“微信”的语句<br /><em>[0-9\.\-]</em>匹配所有的数字，句号和减号<br /><em>^[a-zA-Z_]$</em>所有的字母和下划线<br /><em>^[[:alpha:]]{3}$</em>所有的3个字母的单词<br /><em>^a{4}$</em>aaaa<br /><em>^a{2,4}$</em>aa，aaa或aaaa<br /><em>^a{2,}$</em>匹配多于两个a的字符串';
		$scope.trigger.descriptions.trustee = '如果没有比这条回复优先级更高的回复被触发，那么直接使用这条回复。<br/><strong>注意：如果你不明白这个机制的工作方式，请不要使用直接接管</strong>';
		$scope.trigger.labels = {};
		$scope.trigger.labels.contains = '包含关键字';
		$scope.trigger.labels.regexp = '正则表达式模式';
		$scope.trigger.labels.trustee = '直接接管操作';
		$scope.trigger.active = 'contains';
		$scope.trigger.items = {};
		$scope.trigger.items.default = '';
		$scope.trigger.items.contains = [];
		$scope.trigger.items.regexp = [];
		$scope.trigger.items.trustee = [];
		if($scope.reply.entry) {
			$scope.reply.entry.istop = $scope.reply.entry.displayorder >= 255;
			$scope.reply.advSetting = $scope.reply.entry.displayorder!=0 || !$scope.reply.entry.status;
			if($scope.reply.entry.keywords) {
				angular.forEach($scope.reply.entry.keywords, function(v, k){
					if(v.type == '1') {
						this.default += (v.content + ',');
					}
					if(v.type == '2') {
						this.contains.push({content: v.content, label: '请输入' + $scope.trigger.labels.contains, saved: true});
					}
					if(v.type == '3') {
						this.regexp.push({content: v.content, label: '请输入' + $scope.trigger.labels.regexp, saved: true});
					}
					if(v.type == '4') {
						this.trustee.push({});
					}
				}, $scope.trigger.items);
				if($scope.trigger.items.default.length > 1) {
					$scope.trigger.items.default = $scope.trigger.items.default.slice(0, $scope.trigger.items.default.length - 1);
				}
				if($scope.trigger.items.contains.length > 0 || $scope.trigger.items.regexp.length > 0 || $scope.trigger.items.trustee.length > 0) {
					$scope.reply.advTrigger = true;
				}
				if($scope.trigger.items.contains.length > 0) {
					$('a[data-toggle="tab"]').eq(0).tab('show');
					$scope.trigger.active = 'contains';
				} else if($scope.trigger.items.regexp.length > 0) {
					$('a[data-toggle="tab"]').eq(1).tab('show');
					$scope.trigger.active = 'regexp';
				} else if($scope.trigger.items.trustee.length > 0) {
					$('a[data-toggle="tab"]').eq(2).tab('show');
					$scope.trigger.active = 'trustee';
				}
			}
		}
		$scope.trigger.addItem = function(){
			var type = $scope.trigger.active;
			if(type != 'trustee') {
				$scope.trigger.items[type].push({content: '', label: '请输入' + $scope.trigger.labels[type], saved: false});
			} else {
				if($scope.trigger.items.trustee.length == 0) {
					$scope.trigger.items.trustee.push({});
				}
			}
		};
		$scope.trigger.saveItem = function(item){
			item.saved = !item.saved;
		};
		$scope.trigger.removeItem = function(item) {
			var type = $scope.trigger.active;
			$scope.trigger.items[type] = _.without($scope.trigger.items[type], item);
			$scope.$digest();
		}
		if($.isFunction(window.initReplyController)) {
			window.initReplyController($scope, $http);
		}
		$('#reply-form').submit(function(){
			if($.trim($(':text[name="name"]').val()) == '') {
				util.message('必须输入回复规则名称');
				return false;
			}
			var val = [];
			$scope.$digest();
			if($scope.trigger.items.default != '') {
				var kws = $scope.trigger.items.default.split(',');
				kws = _.union(kws);
				angular.forEach(kws, function(v){
					if(v != '') {
						val.push({type: 1, content: v});
					}
				}, val);
			}
			angular.forEach($scope.trigger.items, function(v, name){
				if(name != 'default' && v.length > 0) {
					angular.forEach(v, function(value){
						var o = {};
						switch(name) {
							case 'contains':
								o.type = 2;
								break;
							case 'regexp':
								o.type = 3;
								break;
							case 'trustee':
								o.type = 4;
								break;
						}
						if(name != 'trustee') {
							o.content = value.content;
						}
						this.push(o);
					}, val);
				}
			}, val);
			if(val.length == 0) {
				util.message('请输入有效的触发关键字.');
				return false;
			}
			$scope.$digest();
			val = angular.toJson(val);
			$(':hidden[name=keywords]').val(val);

			if($.isFunction(window.validateReplyForm)) {
				return window.validateReplyForm(this, $, _, util, $scope, $http);
			}
			return false;
		});
		$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
			$scope.trigger.active = e.target.hash.replace(/#/, '');
			$scope.$digest();
		})
	}).filter('nl2br', function($sce){
		return function(text) {
			return text ? $sce.trustAsHtml(text.replace(/\n/g, '<br/>')) : '';
		};
	}).directive('ngEditor', function($document){
		return function (scope, element, attr) {
			var content = scope.$eval(attr.ngEditor);
			var elm = element[0];
			if(!elm.editor) {
				require(['util'], function(util){
					util.editor(elm, function(e, editor){
						e.editor = editor;
						e.editor.on('init', function(){
							e.editor.setContent(content);
						});
					});
				});
			}
		};
	}).directive('ngInvoker', function($parse){
		return function (scope, element, attr) {
			scope.$eval(attr.ngInvoker);
		};
	});
	angular.bootstrap(document, ['app']);
});
</script>
<div class="clearfix ng-cloak" ng-controller="replyForm">
	<form id="reply-form" class="form-horizontal form" action="<?php  echo url('extension/service/post', array('m' => $m, 'rid' => $rid))?>" method="post" enctype="multipart/form-data">
		<div class="page-header">
			<h4><?php  if(empty($rid)) { ?>添加<?php  } else { ?>修改<?php  } ?>常用服务 <small>服务是最常用的一类回复, 如天气预报, 笑话, 百科, 翻译等简单功能</small></h4>
		</div>
		<div class="form-group">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">服务名称</label>
			<div class="col-sm-8 col-lg-8 col-xs-12">
				<input type="text" class="form-control" placeholder="请输入服务名称" name="name" value="<?php  if(empty($reply['name']) && !empty($_GPC['name'])) { ?><?php  echo $_GPC['name'];?><?php  } else { ?><?php  echo $reply['name'];?><?php  } ?>" />
				<span class="help-block">
					<strong>选择高级设置: 将会提供一系列的高级选项供专业用户使用.</strong>
				</span>
			</div>
			<div class="col-sm-2 col-lg-2">
				<div class="checkbox">
					<label>
						<input type="checkbox" ng-model="reply.advSetting" /> 高级设置
					</label>
				</div>
			</div>
		</div>
		<div class="form-group" ng-show="reply.advSetting">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">状态</label>
			<div class="col-sm-8 col-lg-8">
				<label class="radio-inline">
					<input type="radio" name="status" value="1" <?php  if($reply['status'] == 1 || empty($reply['status'])) { ?> checked="checked"<?php  } ?> /> 启用
				</label>
				<label class="radio-inline">
					<input type="radio" name="status" value="0" <?php  if(!empty($reply) && $reply['status'] == 0) { ?> checked="checked"<?php  } ?> /> 禁用
				</label>
				<span class="help-block">您可以临时禁用这条回复.</span>
			</div>
		</div>
		<div class="form-group" ng-show="reply.advSetting">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">置顶回复</label>
			<div class="col-sm-8 col-lg-8">
				<label class="radio-inline">
					<input type="radio" name="istop" ng-model="reply.entry.istop" ng-value="true" value="1" <?php  if(!empty($reply['displayorder']) && $reply['displayorder'] == 255) { ?> checked="checked"<?php  } ?> /> 置顶
				</label>
				<label class="radio-inline">
					<input type="radio" name="istop" ng-model="reply.entry.istop" ng-value="false" value="0" <?php  if($reply['displayorder'] < 255) { ?> checked="checked"<?php  } ?> /> 普通
				</label>
				<span class="help-block">“置顶”时无论在什么情况下均能触发且使终保持最优先级</span>
			</div>
		</div>
		<div class="form-group" ng-show="reply.advSetting && !reply.entry.istop">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">优先级</label>
			<div class="col-sm-8 col-lg-8">
				<input type="text" class="form-control" placeholder="输入这条回复规则优先级" name="displayorder" value="<?php  echo $reply['displayorder'];?>">
				<span class="help-block">规则优先级，越大则越靠前，最大不得超过254</span>
			</div>
		</div>
		<div class="form-group">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">功能介绍</label>
			<div class="col-sm-8 col-lg-8 col-xs-12">
				<input type="text" class="form-control" placeholder="请输入功能介绍" name="description" value="<?php  echo $reply['description'];?>" />
			</div>
		</div>
		<div class="form-group">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">触发关键字</label>
			<div class="col-sm-8 col-lg-8 col-xs-12">
				<input type="text" class="form-control" placeholder="请输入触发关键字" ng-model="trigger.items.default" />
				<input type="hidden" name="keywords"/>
				<span class="help-block">
					当用户的对话内容符合以上的关键字定义时，会触发这个回复定义。多个关键字请使用逗号隔开. <br />
					<strong>选择高级触发: 将会提供一系列的高级触发方式供专业用户使用(注意: 如果你不了解, 请不要使用). </strong>
				</span>
			</div>
			<div class="col-sm-2 col-lg-2">
				<div class="checkbox">
					<label>
						<input type="checkbox" ng-model="reply.advTrigger" /> 高级触发
					</label>
				</div>
			</div>
		</div>
		<div class="form-group" ng-show="reply.advTrigger">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">高级触发列表</label>
			<div class="col-sm-8 col-lg-8">
				<div class="panel panel-default tab-content">
					<div class="panel-heading">
						<ul class="nav nav-pills">
							<li class="active"><a href="#contains" data-toggle="tab">包含关键字</a></li>
							<li><a href="#regexp" data-toggle="tab">正则表达式模式匹配</a></li>
							<li><a href="#trustee" data-toggle="tab">直接接管</a></li>
						</ul>
					</div>
					<ul class="tab-pane list-group active" id="contains">
						<li class="list-group-item row" ng-repeat="entry in trigger.items.contains">
							<div class="col-xs-12 col-sm-8">
								<input type="text" class="form-control" ng-hide="entry.saved" placeholder="{{entry.label}}" ng-model="entry.content" />
								<p class="form-control-static" ng-show="entry.saved" ng-bind="entry.content"></p>
							</div>
							<div class="col-sm-4">


								<div class="btn-group">
									<a href="javascript:;" class="btn btn-default" ng-click="trigger.saveItem(entry);">{{entry.saved ? '编辑' : '保存'}}</a>
									<a href="javascript:;" class="btn btn-default" ng-click="trigger.removeItem(entry);">删除</a>
								</div>
							</div>
						</li>
					</ul>
					<ul class="tab-pane list-group" id="regexp">
						<li class="list-group-item row" ng-repeat="entry in trigger.items.regexp">
							<div class="col-xs-12 col-sm-8">
								<input type="text" class="form-control" ng-hide="entry.saved" placeholder="{{entry.label}}" ng-model="entry.content" />
								<p class="form-control-static" ng-show="entry.saved" ng-bind="entry.content"></p>
							</div>
							<div class="col-sm-4">
								<div class="btn-group">
									<a href="javascript:;" class="btn btn-default" ng-click="trigger.saveItem(entry);">{{entry.saved ? '编辑' : '保存'}}</a>
									<a href="javascript:;" class="btn btn-default" ng-click="trigger.removeItem(entry);">删除</a>
								</div>
							</div>
						</li>
					</ul>
					<ul class="tab-pane list-group" id="trustee">
						<li class="list-group-item row" ng-repeat="entry in trigger.items.trustee">
							<div class="col-xs-12 col-sm-8">
								<p class="form-control-static">符合优先级条件时, 这条回复将直接生效</p>
							</div>
							<div class="col-sm-4">
								<a href="javascript:;" class="btn btn-default" ng-click="trigger.removeItem(entry);">取消接管</a>
							</div>
						</li>
					</ul>
					<div class="panel-footer">
						<a href="javascript:;" class="btn btn-default" ng-click="trigger.addItem();" ng-bind="'添加' + trigger.labels[trigger.active]">添加</a>
						<span class="help-block" ng-bind-html="trigger.descriptions[trigger.active]"></span>
					</div>
				</div>
			</div>
		</div>
		<div class="form-group">
			<label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">回复内容</label>
			<div class="col-sm-8 col-lg-8 col-xs-12">
				<?php  echo module_build_form($m, $rid);?>
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-2 col-md-offset-2 col-lg-offset-2 col-xs-12 col-sm-10 col-md-10 col-lg-10">
				<input name="submit" type="submit" value="提交" class="btn btn-primary" />
				<input type="hidden" name="token" value="<?php  echo $_W['token'];?>" />
			</div>
		</div>
	</form>
</div>
<?php (!empty($this) && $this instanceof WeModuleSite || 0) ? (include $this->template('common/footer-gw', TEMPLATE_INCLUDEPATH)) : (include template('common/footer-gw', TEMPLATE_INCLUDEPATH));?>