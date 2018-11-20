<?php
namespace Common\Mongo;

/**
 * Mongodb表名定义
 */

class TableName{

    const GLOBALS           = 'globals';

    const SERVER            = 'server';

    const ANSIBLE_LOG       = 'ansible_log';

    const USER_LOG          = 'user_log';

    const SUCCESS_LOG       = 'success_log';

    const ERROR_LOG       = 'error_log';

    const DO_LOG       = 'do_log';

    const ORDERS_ERROR_LOG       = 'orders_error_log';

    const ORDERS_SUCCESS_LOG       = 'orders_success_log';

    const GET_ACT_LOG       = 'get_act_log';

    const GET_GS_LOG       = 'get_gs_log';

    const GET_ORDER_ID_LOG       = 'get_order_id_log';

}