<?php

/**
 * TeaModel类文件。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaModel {

    /**
     * 类配置数组。
     * @var array
     */
    public static $config = array(
        'arrayResult' => true, // 是否数组化结果集，false则结果集为object。
        'defaultConnection' => 'default', // 默认数据库连接key。
        'connections' => array( // 数据库连接信息组。
            'default' => array( // 数据库连接key名。
                'dsn' => 'mysql:host=127.0.0.1;dbname=test;', // 数据库连接dsn，请参考pdo dsn写法，如mysql的写法http://php.net/manual/en/ref.pdo-mysql.connection.php。
                'username' => 'root', // 数据库用户名。
                'password' => '123456', // 数据库密码。
                'charset' => 'utf8', // 数据库字符集，如果字符集已经在dsn里设置，则此项无效。
                'tablePrefix' => 'tb_', // 数据库表前缀。
                'aliasMark' => '->', // 数据库表别名连接符，在有表名处使用即可创建别名。如'my_table->a'，则'a'代表'my_table'。
                'tableColumnLinkMark' => '-', // join查询自动表前缀连接符，这在多张表有字段相同时非常有用，不用担心表名相同而引起的值覆盖。
                'persistent' => true, // 是否持久连接，建议开启。
                'emulatePrepare' => true, // 是否模拟prepare，建议开启。
                'autoConnect' => true // 是否自动连接，建议开启。
            )
        )
    );

    /**
     * 表字段名。
     * @var array
     */
    private $_colNames = array();

    /**
     * 表主键字段名。
     * @var array
     */
    private $_pkColNames = array();

    /**
     * 由TeaModel::criterias()或者TeaModel::relations()生成的条件数组。
     * @var array
     */
    private $_addonCriteriaArr = array();

    /**
     * 是否数组化结果集，false则结果集为object。
     * @var boolean
     */
    private $_arrayResult = false;

    /**
     * 构造函数，加载配置。
     */
    public function __construct() {
        Tea::setClassConfig(__CLASS__);
        $this->_arrayResult = Tea::getConfig('TeaModel.arrayResult');
    }

    /**
     * 魔术方法__get。
     * 此方法用于获取由魔术方法__set设置的字段值。
     * @param string $name 字段名。
     * @return mixed 被设置的字段值。
     */
    public function __get($name) {
        if (isset($this->{$name})) {
            return $this->{$name};
        } else {
            $trace = debug_backtrace();
            trigger_error("Undefined property via __get(): {$name} in {$trace[0]['file']} on line {$trace[0]['line']}");
            return null;
        }
    }

    /**
     * 魔术方法__set。
     * 此方法用于设置字段值。
     * @param string $name 字段名。
     * @param mixed $value 字段值。
     */
    public function __set($name, $value) {
        $this->{$name} = $value;
    }

    /**
     * 钩子函数。
     * 请为当前模型返回表名，默认为小写化和下划线化的模型类名，如'FooBarModel'则会自动判断表名为'foo_bar'。
     * 手动设置表名，格式请用'{{table_name}}', 因为如果配置了表前缀如'tb_'，此格式将会自动判断生成表名'tb_table_name'。
     * 当然，如果不想受表前缀配置的影响，则直接指定表名字符串来强制使用表名，如'table_name'，则会判断表名就为'table_name'。
     * @return string 返回有格式或无格式的表名。
     */
    public function tableName() {
        $modelName = get_class($this);
        return StringHelper::camelToUnderscore(preg_replace('/(.+)Model/', '$1', $modelName));
    }

    /**
     * 钩子函数。
     * 请为当前模型返回通用条件，默认为空数组。
     * <pre>
     * 数组格式如下：
     * array(
     *     'criteriaOne' => array(
     *         'join' => array('left:bar' => array('bar.pid' => 'foo.id')),
     *         'where' => array('foo.id:lte' => 10)
     *     ),
     *     'criteriaTwo' => array(
     *         'where' => array('blabla.id:between' => array(10, 100))
     *     ),
     *     'criteriaThree' => Tea::getDbCriteria()->where(array('foo.id:lte' => 5))
     *     ...
     * )
     * </pre>
     * @return array 条件映射数组。
     */
    public function criterias() {
        return array();
    }

    /**
     * 附加一个或多个通用条件来查询。
     * @param string $criteriaName,... 非定长参数，值为TeaModel::criterias()返回数组的键名。
     * @return $this
     */
    public function withCriteria() {
        $criterias = $this->criterias();
        $criteriaNames = func_get_args();
        foreach ($criteriaNames as $criteriaName) {
            if (array_key_exists($criteriaName, $criterias)) {
                $addonCriteria = $criterias[$criteriaName];
                if ($addonCriteria instanceof TeaDbCriteria) {
                    $this->_addonCriteriaArr = ArrayHelper::mergeArray($this->_addonCriteriaArr, $addonCriteria->criteriaArr);
                } else if (is_array($addonCriteria) && !empty($addonCriteria)) {
                    $this->_addonCriteriaArr = ArrayHelper::mergeArray($this->_addonCriteriaArr, $addonCriteria);
                }
            }
        }
        return $this;
    }

    /**
     * 钩子函数。
     * 这是TeaModel::criterias()的一个便捷方法。
     * 请为当前模型返回join通用条件映射数组，默认为空数组。
     * <pre>
     * 数组格式如下：
     * array(
     *     'joinOne' => array(
     *         'left:bar' => array('bar.pid' => 'foo.id'),
     *     ),
     *     'joinTwo' => array(
     *         'bla' => array('bla.pid' => 'foo.id'),
     *     ),
     *     'joinThree' => Tea::getDbCriteria()->join(array('right:bla' => array('bla.pid' => 'foo.id')))
     *     ...
     * )
     * </pre>
     * @return array join通用条件映射数组。
     */
    public function joins() {
        return array();
    }

    /**
     * 附加一个或多个join通用条件来查询。
     * @param string $joinName,... 非定长参数，值为TeaModel::joins()返回数组的键名。
     * @return $this
     */
    public function withJoin() {
        $joins = $this->joins();
        $joinNames = func_get_args();
        foreach ($joinNames as $joinName) {
            if (array_key_exists($joinName, $joins)) {
                $addonJoin = $joins[$joinName];
                if ($addonJoin instanceof TeaDbCriteria) {
                    $joinCriteria = array();
                    if (isset($addonJoin->criteriaArr['join'])) {
                        $joinCriteria = array('join' => $addonJoin->criteriaArr['join']);
                    }
                    $this->_addonCriteriaArr = ArrayHelper::mergeArray($this->_addonCriteriaArr, $joinCriteria);
                } else if (is_array($addonJoin) && !empty($addonJoin)) {
                    $this->_addonCriteriaArr = ArrayHelper::mergeArray($this->_addonCriteriaArr, array('join' => $addonJoin));
                }
            }
        }
        return $this;
    }

    /**
     * 获取真实表名。
     * 如果表名设置为'{{table_name}}', 且配置的表前缀为'tbl_'，则将返回'tbl_table_name'。
     * @return string 真实表名。
     */
    public function getTableName() {
        return Tea::getDbSqlBuilder()->getTableName($this->tableName());
    }

    /**
     * 获取设置的表别名。
     * 如果表名设置为'{{table_name->A}}'，则将返回别名'A'。
     * @return string 表别名。
     */
    public function getTableAlias() {
        return Tea::getDbSqlBuilder()->getTableAlias($this->tableName());
    }

    /**
     * 转换结果集获取风格是否为数组。
     * @param bool $status true表示结果集为array，false表示结果集为object。
     * @return $this
     */
    public function arrayResult($status) {
        $this->_arrayResult = $status ? true : false;
        return $this;
    }

    /**
     * 插入一条或者多条数据。
     * @param mixed $vals 插入数据。
     * <pre>
     * 插入的数据有三种格式。
     *
     * 第一种为一维数组，表示一条数据：
     * array(col1Val, col2Val, colNVal, ...)
     * 或者
     * array(
     *     'col1Name' => col1Val,
     *     'col2Name' => col2Val,
     *     'colNName' => colNVal,
     *     ...
     * )
     *
     * 第二种为二维数组，表示多条数据：
     * array(
     *     array(col1Val, col2Val, colNVal),
     *     array(col1Val, col2Val, colNVal),
     *     ...
     * )
     * 或者
     * array(
     *     array('col1Name' => col1Val, 'col2Name' => col2Val, 'colNName' => colNVal),
     *     array(col1Val, col2Val, colNVal),
     *     ... // 指定键名插入数据，可以只在第一条数据提供键名，其他的数据可以省略。注意：不支持每条数据键名不同或者长度不等。
     * )
     *
     * 第三种为SELECT查询语句，可以通过TeaDbSqlBuilder::select()来生成：
     * 如：'SELECT * FROM `table`'
     * </pre>
     * @param array $duplicateUpdate 如果有重复字段，则需要更新的数据。
     * <pre>
     * 数据格式如下：
     * array(
     *     'colName1' => colVal1,
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @return bool
     */
    public function insert($vals = array(), $duplicateUpdate = array()) {
        if (empty($vals)) {
            $vals = $duplicateUpdate = $this->getSetRecord();
        }
        $criteria = is_array($duplicateUpdate) && !empty($duplicateUpdate) ? array('duplicateUpdate' => $duplicateUpdate) : null;
        $sql = Tea::getDbSqlBuilder()->insert($this->tableName(), $vals, $this->getProperCriteria($criteria));
        $this->onBeforeSave();
        if (Tea::getDbQuery()->query($sql)->getRowCount() > 0) {
            $this->onAfterSave();
            return true;
        }
        return false;
    }

    /**
     * 根据条件获取一条数据。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return mixed 根据获取风格返回数组或对象，请用empty()来判断是否获取成功。
     */
    public function find($criteria = array(), $exprs = null) {
        $this->onBeforeFind();
        $properCriteria = $this->getProperCriteria($criteria);
        $sql = Tea::getDbSqlBuilder()->select($this->tableName(), $properCriteria, $this->getProperExprs($properCriteria, $exprs));
        $modelName = get_class($this);
        if ($this->_arrayResult) {
            $data = Tea::getDbQuery()->query($sql)->fetchRow();
        } else {
            if ($modelName === 'TeaTempModel') {
                $data = Tea::getDbQuery()->query($sql)->fetchObj($modelName, array($this->tableName()));
            } else {
                $data = Tea::getDbQuery()->query($sql)->fetchObj($modelName);
            }
        }
        $this->_arrayResult = Tea::getConfig('TeaModel.arrayResult');
        $this->onAfterFind();
        return $data;
    }

    /**
     * 根据sql获取一条数据。
     * @param string $sql sql语句。
     * @param array $params sql语句绑定参数键值对数组。
     * @return mixed 根据获取风格返回数组或对象，请用empty()来判断是否获取成功。
     */
    public function findBySql($sql, $params = array()) {
        $this->onBeforeFind();
        $modelName = get_class($this);
        if ($this->_arrayResult) {
            $data = Tea::getDbQuery()->query($sql, $params)->fetchRow();
        } else {
            if ($modelName === 'TeaTempModel') {
                $data = Tea::getDbQuery()->query($sql, $params)->fetchObj($modelName, array($this->tableName()));
            } else {
                $data = Tea::getDbQuery()->query($sql, $params)->fetchObj($modelName);
            }
        }
        $this->_arrayResult = Tea::getConfig('TeaModel.arrayResult');
        $this->onAfterFind();
        return $data;
    }

    /**
     * 根据条件where获取一条数据。
     * @param array $condition 条件where数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return mixed 根据获取风格返回数组或对象，请用empty()来判断是否获取成功。
     */
    public function findByCondition($condition = array(), $exprs = null) {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->find($criteria, $exprs);
    }

    /**
     * 通过主键值获取一条数据。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return mixed 根据获取风格返回数组或对象，请用empty()来判断是否获取成功。
     */
    public function findByPk($pkVal, $exprs = null) {
        return $this->find($this->getPkCriteria($pkVal), $exprs);
    }

    /**
     * 获取字段值。
     * @param mixed $rowRst 一条数据的object或者array。
     * @param string $column 字段名。
     * @return mixed 成功返回字段值，失败返回false。
     */
    public function getColumnValue($rowRst, $column) {
        $modelName = get_class($this);
        if (is_array($rowRst) && is_string($column) && isset($rowRst[$column])) {
            $data = $rowRst[$column];
        } else if ($rowRst instanceof $modelName && is_string($column) && property_exists($rowRst, $column)) {
            $data = $rowRst->{$column};
        } else {
            $data = false;
        }
        return $data;
    }

    /**
     * 根据条件获取所有数据。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return array 根据获取风格返回数组或对象数组，请用empty()来判断是否获取成功。
     */
    public function findAll($criteria = array(), $exprs = null) {
        $this->onBeforeFind();
        $properCriteria = $this->getProperCriteria($criteria);
        $sql = Tea::getDbSqlBuilder()->select($this->tableName(), $properCriteria, $this->getProperExprs($properCriteria, $exprs));
        $modelName = get_class($this);
        if ($this->_arrayResult) {
            $data = Tea::getDbQuery()->query($sql)->fetchRows();
        } else {
            if ($modelName === 'TeaTempModel') {
                $data = Tea::getDbQuery()->query($sql)->fetchObjs($modelName, array($this->tableName()));
            } else {
                $data = Tea::getDbQuery()->query($sql)->fetchObjs($modelName);
            }
        }
        $this->_arrayResult = Tea::getConfig('TeaModel.arrayResult');
        $this->onAfterFind();
        return $data;
    }

    /**
     * 根据sql获取所有数据。
     * @param string $sql sql语句。
     * @param array $params sql语句绑定参数键值对数组。
     * @return array 根据获取风格返回数组或对象数组，请用empty()来判断是否获取成功。
     */
    public function findAllBySql($sql, $params = array()) {
        $this->onBeforeFind();
        $modelName = get_class($this);
        if ($this->_arrayResult) {
            $data = Tea::getDbQuery()->query($sql, $params)->fetchRows();
        } else {
            if ($modelName === 'TeaTempModel') {
                $data = Tea::getDbQuery()->query($sql, $params)->fetchObjs($modelName, array($this->tableName()));
            } else {
                $data = Tea::getDbQuery()->query($sql, $params)->fetchObjs($modelName);
            }
        }
        $this->_arrayResult = Tea::getConfig('TeaModel.arrayResult');
        $this->onAfterFind();
        return $data;
    }

    /**
     * 根据条件where获取所有数据。
     * @param array $condition 条件where数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return array 根据获取风格返回数组或对象数组，请用empty()来判断是否获取成功。
     */
    public function findAllByCondition($condition = array(), $exprs = null) {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->findAll($criteria, $exprs);
    }

    /**
     * 根据条件判断数据是否存在。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param string $alias 别名，默认为'exists'。
     * @return bool
     */
    public function exists($criteria = array(), $alias = 'exists') {
        $sql = Tea::getDbSqlBuilder()->exists($this->tableName(), $this->getProperCriteria($criteria), $alias);
        $rst = $this->findBySql($sql);
        $existsVal = $this->getColumnValue($rst, $alias);
        return intval($existsVal) === 1 ? true : false;
    }

    /**
     * 根据条件where判断数据是否存在。
     * @param mixed $condition 条件where数组。
     * @param string $alias 别名，默认为'exists'。
     * @return bool
     */
    public function existsByCondition($condition = array(), $alias = 'exists') {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->exists($criteria, $alias);
    }

    /**
     * 通过主键值判断数据是否存在。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @return bool
     */
    public function existsByPk($pkVal) {
        return $this->exists($this->getPkCriteria($pkVal));
    }

    /**
     * 根据条件获取数据条目数。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param string $alias 别名，默认为'total'。
     * @return mixed 成功返回条目数，失败则返回false。
     */
    public function count($criteria = array(), $alias = 'total') {
        $sql = Tea::getDbSqlBuilder()->count($this->tableName(), $this->getProperCriteria($criteria), $alias);
        $rst = $this->findBySql($sql);
        $totalVal = $this->getColumnValue($rst, $alias);
        return $totalVal === false ? false : intval($totalVal);
    }

    /**
     * 根据条件where获取数据条目数。
     * @param array $condition 条件where数组。
     * @param string $alias 别名，默认为'total'。
     * @return mixed 成功返回条目数，失败则返回false。
     */
    public function countByCondition($condition = array(), $alias = 'total') {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->count($criteria, $alias);
    }

    /**
     * 钩子函数，在查找方法前执行，在模型里声明beforeFind方法即可。
     */
    public function onBeforeFind() {
        if (method_exists($this, 'beforeFind')) {
            $this->beforeFind();
        }
    }

    /**
     * 钩子函数，在查找方法后执行，在模型里声明afterFind方法即可。
     */
    public function onAfterFind() {
        if (method_exists($this, 'afterFind')) {
            $this->afterFind();
        }
    }

    /**
     * 根据条件更新一条或者多条数据。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param array $vals 更新数据。
     * <pre>
     * 更新数据格式如下：
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @param bool $safe 是否安全更新，默认为true。true表示无论如何都只更新一条数据，false表示会根据条件更新所有符合的数据。
     * @return bool
     */
    public function update($criteria = array(), $vals = array(), $safe = true) {
        if (empty($vals)) {
            $vals = $this->getSetRecord();
        }
        if ($safe) {
            if ($criteria instanceof TeaDbCriteria) {
                $criteria->limit(array(1));
            } else if (is_array($criteria)) {
                $criteria['limit'] = array(1);
            }
        }
        $sql = Tea::getDbSqlBuilder()->update($this->tableName(), $vals, $this->getProperCriteria($criteria));
        $this->onBeforeSave();
        if (Tea::getDbQuery()->query($sql)->getRowCount() > 0) {
            $this->onAfterSave();
            return true;
        }
        return false;
    }

    /**
     * 根据条件where更新一条或者多条数据。
     * @param array $condition 条件where数组。
     * @param array $vals 更新数据。
     * <pre>
     * 更新数据格式如下：
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @param bool $safe 是否安全更新，默认为true。true表示无论如何都只更新一条数据，false表示会根据条件更新所有符合的数据。
     * @return bool
     */
    public function updateByCondition($condition = array(), $vals = array(), $safe = true) {
        $criteria = array('where' => $condition);
        return $this->update($criteria, $vals, $safe);
    }

    /**
     * 通过主键值更新一条数据。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @param array $vals 更新数据。
     * <pre>
     * 更新数据格式如下：
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @return bool
     */
    public function updateByPk($pkVal, $vals = array()) {
        return $this->update($this->getPkCriteria($pkVal), $vals);
    }

    /**
     * 根据条件增加字段值。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param string $colName 需要增加值的字段名。
     * @param int $val 增量，默认为1。
     * @param bool $safe 是否安全更新，默认为true。true表示无论如何都只更新一条数据，false表示会根据条件更新所有符合的数据。
     * @return bool
     */
    public function inc($criteria, $colName, $val = 1, $safe = true) {
        $expr = Tea::getDbSqlBuilder()->quoteColumn($colName) . ' + ' . Tea::getDbQuery()->escape($val);
        $vals = array(
            $colName => new TeaDbExpr($expr)
        );
        return $this->update($this->getProperCriteria($criteria), $vals, $safe);
    }

    /**
     * 根据条件where增加字段值。
     * @param array $condition 条件where数组。
     * @param string $colName 需要增加值的字段名。
     * @param int $val 增量，默认为1。
     * @param bool $safe 是否安全更新，默认为true。true表示无论如何都只更新一条数据，false表示会根据条件更新所有符合的数据。
     * @return bool
     */
    public function incByCondition($condition, $colName, $val = 1, $safe = true) {
        $criteria = array('where' => $condition);
        return $this->inc($criteria, $colName, $val, $safe);
    }

    /**
     * 通过主键值增加字段值。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @param string $colName 需要增加值的字段名。
     * @param int $val 增量，默认为1。
     * @return bool
     */
    public function incByPk($pkVal, $colName, $val = 1) {
        return $this->inc($this->getPkCriteria($pkVal), $colName, $val);
    }

    /**
     * 保存数据。如果数据存在，将会执行更新，否则执行插入。
     * @param mixed $vals 保存数据，数据格式请参考TeaModel::insert()。
     * @return bool
     */
    public function save($vals = array()) {
        return $this->insert($vals, $vals);
    }

    /**
     * 钩子函数，在插入或更新方法前执行，在模型里声明beforeSave方法即可。
     */
    public function onBeforeSave() {
        if (method_exists($this, 'beforeSave')) {
            $this->beforeSave();
        }
    }

    /**
     * 钩子函数，在插入或更新方法后执行，在模型里声明afterSave方法即可。
     */
    public function onAfterSave() {
        if (method_exists($this, 'afterSave')) {
            $this->afterSave();
        }
    }

    /**
     * 根据条件删除一条或者多条数据。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param bool $safe 是否安全删除，默认为true。true表示无论如何都只删除一条数据，false表示会根据条件删除所有符合的数据。
     * @return bool
     */
    public function delete($criteria = array(), $safe = true) {
        if ($safe) {
            if ($criteria instanceof TeaDbCriteria) {
                $criteria->limit(array(1));
            } else if (is_array($criteria)) {
                $criteria['limit'] = array(1);
            }
        }
        $sql = Tea::getDbSqlBuilder()->delete($this->tableName(), $this->getProperCriteria($criteria));
        $this->onBeforeDelete();
        if (Tea::getDbQuery()->query($sql)->getRowCount() > 0) {
            $this->onAfterDelete();
            return true;
        }
        return false;
    }

    /**
     * 根据条件where删除一条或者多条数据。
     * @param array $condition 条件where数组。
     * @param bool $safe 是否安全删除，默认为true。true表示无论如何都只删除一条数据，false表示会根据条件删除所有符合的数据。
     * @return bool
     */
    public function deleteByCondition($condition = array(), $safe = true) {
        $criteria = is_array($condition) && !empty($condition) ? array('where' => $condition) : null;
        return $this->delete($criteria, $safe);
    }

    /**
     * 通过主键值删除一条数据。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @return bool
     */
    public function deleteByPk($pkVal) {
        return $this->delete($this->getPkCriteria($pkVal));
    }

    /**
     * 钩子函数，在删除方法前执行，在模型里声明beforeDelete方法即可。
     */
    public function onBeforeDelete() {
        if (method_exists($this, 'beforeDelete')) {
            $this->beforeDelete();
        }
    }

    /**
     * 钩子函数，在删除方法后执行，在模型里声明afterDelete方法即可。
     */
    public function onAfterDelete() {
        if (method_exists($this, 'afterDelete')) {
            $this->afterDelete();
        }
    }

    /**
     * 获取最后执行的INSERT, SELECT, UPDATE or DELETE sql语句所影响的条数。
     * @return int
     */
    public function getRowCount() {
        return Tea::getDbQuery()->getRowCount();
    }

    /**
     * 获取最后插入的数据的ID。
     * 注意：不支持一次插入多行数据。
     * @return int 最后插入的数据的ID。
     */
    public function getLastInsertId() {
        return Tea::getDbQuery()->getLastInsertId();
    }

    /**
     * 获取最后执行的sql语句。
     * @return string 最后执行的sql语句。
     */
    public function getLastSql() {
        return Tea::getDbQuery()->getLastSql();
    }

    /**
     * 获取当前表的所有字段名。
     * @return array 当前表的所有字段名。
     */
    public function getColumnNames() {
        if (is_array($this->_colNames) && !empty($this->_colNames)) {
            return $this->_colNames;
        }
        return $this->_colNames = array_keys(Tea::getDbSchema()->getTableColumns($this->tableName()));
    }

    /**
     * 判断名称是否为当前表中的字段名。
     * @param string $name 被检测的名称。
     * @return bool
     */
    public function isTableColumn($name) {
        return in_array($name, $this->getColumnNames()) ? true : false;
    }

    /**
     * 获取主键的字段名数组。
     * @return array 主键的字段名数组。
     */
    public function getPkColumnNames() {
        if (is_array($this->_pkColNames) && !empty($this->_pkColNames)) {
            return $this->_pkColNames;
        }
        return $this->_pkColNames = Tea::getDbSchema()->getPkColumnNames($this->tableName());
    }

    /**
     * 根据主键获取相应的条件数组。
     * @param mixed $pkVal 单字段主键值或者多字段主键值数组。
     * @return array 条件数组。
     * @throws TeaDbException
     */
    public function getPkCriteria($pkVal) {
        $criteria = array();
        $pkColNames = $this->getPkColumnNames();
        if (is_array($pkVal)) {
            if (count($pkColNames) !== count($pkVal)) {
                $pkValStr = implode(', ', $pkColNames);
                throw new TeaDbException("PRIMARY key columns array({$pkValStr}) and parameter 1 should have an equal number of elements.");
            }
            $where = array_combine($pkColNames, $pkVal);
            $criteria = array('where' => $where);
        } else {
            $criteria = array('where' => array($pkColNames[0] => $pkVal));
        }
        return $criteria;
    }

    /**
     * 获取设置的记录。
     * @return array
     */
    public function getSetRecord() {
        $properties = get_object_vars($this);
        $record = array();
        foreach ($properties as $property => $value) {
            if ($this->isTableColumn($property)) {
                $record[$property] = $value;
            }
        }
        return $record;
    }

    /**
     * 返回TeaModel::$_addonCriteriaArr与$userCriteria合并后合适的条件。
     * @param mixed $userCriteria 需要被合并的条件。
     * @return mixed 合适的条件。
     */
    public function getProperCriteria($userCriteria) {
        if (is_numeric($userCriteria)) {
            $criteria = ArrayHelper::mergeArray($this->_addonCriteriaArr, $this->getPkCriteria($userCriteria));
        } else if ($userCriteria instanceof TeaDbCriteria) {
            $userCriteriaArr = $userCriteria->criteriaArr;
            $criteria = ArrayHelper::mergeArray($this->_addonCriteriaArr, $userCriteriaArr);
        } else if (is_array($userCriteria) && !empty($userCriteria)) {
            $criteria = ArrayHelper::mergeArray($this->_addonCriteriaArr, $userCriteria);
        } else {
            $criteria = $this->_addonCriteriaArr;
        }
        $this->_addonCriteriaArr = array(); // clear after merge
        return $criteria;
    }

    /**
     * 为获取方法返回合适的表达式。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @param mixed $exprs SELECT语句表达式，字符串或者数组，可以在此声明选取的字段和特殊表达式。如果为空，则默认选取所有字段。
     * 要执行原生sql表达式，请使用new TeaDbExpr($expr, $params)。
     * 若$expr为空，且$criteria包含join条件，则会在选取的所有字段前自动加上表前缀。
     * @return mixed 合适的表达式。
     */
    public function getProperExprs($criteria, $exprs) {
        if ($criteria instanceof TeaDbCriteria && isset($criteria->criteriaArr['join'])) {
            $criteriaJoin = $criteria->criteriaArr['join'];
        } else if (is_array($criteria) && isset($criteria['join'])) {
            $criteriaJoin = $criteria['join'];
        } else {
            $criteriaJoin = array();
        }
        if (empty($exprs) && !empty($criteriaJoin)) {
            $tableNames = array($this->tableName() => $this->getColumnNames());
            foreach ($criteriaJoin as $key => $cond) {
                $parts = array_map('trim', explode(TeaDbCriteria::OP_DELIMITER, $key));
                !array_key_exists($parts[0], TeaDbCriteria::$joinTypeMap) && array_unshift($parts, 'inner');
                $joinType = array_shift($parts);
                $joinTableName = array_pop($parts);
                $joinTableColumns = array_keys(Tea::getDbSchema()->getTableColumns($joinTableName));
                $tableNames[$joinTableName] = $joinTableColumns;
            }
            foreach ($tableNames as $tableName => $columns) {
                $prefix = Tea::getDbSqlBuilder()->getTableAlias($tableName);
                if (empty($prefix)) {
                    $prefix = Tea::getDbSqlBuilder()->getTableName($tableName);
                }
                foreach ($columns as $col) {
                    $prefixTable = !empty($prefix) ? $prefix . '.' : '';
                    $prefixCol = !empty($prefix) ? $prefix . Tea::getDbConnection()->tableColumnLinkMark : '';
                    $exprs[] = $prefixTable . $col . Tea::getDbConnection()->aliasMark . $prefixCol . $col;
                }
            }
        }
        return $exprs;
    }

}