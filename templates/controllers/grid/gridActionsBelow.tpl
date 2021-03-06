{**
 * templates/controllers/grid/gridActionsBelow.tpl
 *
 * Copyright (c) 2000-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Grid actions in bottom position
 *}

<div class="actions pkp_linkActions grid_link_actions_below">
	{foreach from=$grid->getActions($smarty.const.GRID_ACTION_POSITION_BELOW) item=action}
		{include file="linkAction/linkAction.tpl" action=$action contextId=$gridId}
	{/foreach}
	<div class="pkp_helpers_clear"></div>
</div>
