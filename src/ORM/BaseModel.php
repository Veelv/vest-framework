<?php

namespace Vest\ORM;

use PDO;
use Vest\Exceptions\RelationshipException;
use Vest\ORM\Cache\CacheManager;
use Vest\Support\Uuid;

abstract class BaseModel
{
    // Conexão PDO estática para acesso ao banco de dados
    private static PDO $connection;

    // Nome da tabela associada ao modelo
    protected string $table;

    // Chave primária da tabela
    protected string $primaryKey = 'id';

    // Definir entre uuid ou id padrão
    protected string $idType = 'id'; // uuid or id

    // Indica se os timestamps devem ser gerenciados automaticamente
    protected bool $timestamps = true;

    // Indica se as exclusões suaves (soft deletes) devem ser aplicadas
    protected bool $softDeletes = false;

    // Atributos que podem ser preenchidos em massa
    protected array $fillable = [];

    // Atributos que estão protegidos contra preenchimento em massa
    protected array $guarded = [];

    // Atributos que devem ser ocultados em arrays e JSON
    protected array $hidden = [];

    // Atributos que devem ser convertidos para tipos específicos
    protected array $casts = [];

    // Atributos do modelo
    protected array $attributes = [];

    protected static CacheManager $cacheManager;

    // Eventos
    protected static array $events = [
        'creating' => [],
        'created' => [],
        'updating' => [],
        'updated' => [],
        'deleting' => [],
        'deleted' => [],
    ];

    // Escopos globais
    protected static array $globalScopes = [];

    /**
     * Construtor que preenche os atributos iniciais do modelo.
     *
     * @param array $attributes Atributos a serem preenchidos no modelo.
     */
    public function __construct(array $attributes = [])
    {
        if (!isset($attributes[$this->primaryKey])) {
            if ($this->idType === 'uuid') {
                $attributes[$this->primaryKey] = Uuid::generate();
            } else {
                $attributes[$this->primaryKey] = null; // ou algum outro valor padrão
            }
        }
        $this->fill($attributes);
    }

    /**
     * Define a conexão PDO a ser usada pelos modelos.
     *
     * @param PDO $connection Instância de conexão PDO.
     */
    public static function setConnection(PDO $connection): void
    {
        self::$connection = $connection;
    }

    public static function setCacheManager(CacheManager $cacheManager): void
    {
        self::$cacheManager = $cacheManager;
    }

    // Método para registrar eventos
    public static function registerEvent(string $event, callable $callback): void
    {
        if (array_key_exists($event, self::$events)) {
            self::$events[$event][] = $callback;
        }
    }

    // Método para disparar eventos
    protected static function fireEvent(string $event, $model): void
    {
        foreach (self::$events[$event] as $callback) {
            call_user_func($callback, $model);
        }
    }

    // Método para adicionar escopos globais
    public static function addGlobalScope(callable $scope): void
    {
        self::$globalScopes[] = $scope;
    }

    // Método para aplicar escopos globais
    protected static function applyGlobalScopes(QueryBuilder $query): QueryBuilder
    {
        foreach (self::$globalScopes as $scope) {
            call_user_func($scope, $query);
        }
        return $query;
    }

    /**
     * Preenche os atributos do modelo com os valores fornecidos.
     *
     * @param array $attributes Atributos a serem preenchidos.
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $this->castAttribute($key, $value);
            }
        }
        return $this;
    }

    /**
     * Recupera todos os registros da tabela associada ao modelo.
     *
     * @return array Lista de registros.
     */
    public static function all(): array
    {
        $cacheKey = static::class . ':all';
        $cachedModels = self::$cacheManager->getModelCache()->get($cacheKey);

        if ($cachedModels) {
            return $cachedModels;
        }

        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query = self::applyGlobalScopes($query);
        $results = (new QueryBuilder(self::$connection))->table($instance->getTable())->get();

        self::$cacheManager->getModelCache()->put($cacheKey, $results);

        return $results;
    }

    public static function fech(): array
    {
        $instance = new static;
        $results = (new QueryBuilder(self::$connection))->table($instance->getTable())->get();

        // Retorna os resultados como um array de objetos com chaves nomeadas
        return array_map(function ($row) {
            $row = array_filter($row, function ($key) {
                return !is_numeric($key);
            }, ARRAY_FILTER_USE_KEY);

            $object = (object) []; // Cria um objeto vazio
            foreach ($row as $key => $value) {
                $object->$key = $value;
            }
            return $object;
        }, $results);
    }

    public static function count(array $where = []): int
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        // Adiciona condições WHERE, se fornecidas
        foreach ($where as $field => $value) {
            $query->where($field, '=', $value);
        }

        // Passa a contagem como um array
        $result = $query->select(['COUNT(*) as count'])->get();

        return (int) $result[0]['count'];
    }

    public static function sum(string $field, array $where = []): float
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        // Adiciona condições WHERE, se fornecidas
        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        // Passa a soma como um array
        $result = $query->select(["SUM($field) as total"])->get();

        return (float) $result[0]['total'] ?? 0.0; // Retorna 0.0 se não houver resultados
    }

    public static function min(string $field, array $where = []): float
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        $result = $query->select(["MIN($field) as min"])->get();

        return (float) $result[0]['min'] ?? 0.0;
    }

    public static function max(string $field, array $where = []): float
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        $result = $query->select(["MAX($field) as max"])->get();

        return (float) $result[0]['max'] ?? 0.0;
    }

    public static function first(array $where = []): ?array
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        return $query->select(['*'])->limit(1)->get()[0] ?? null;
    }

    public static function exists(array $where = []): bool
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        $result = $query->select(['COUNT(*) as count'])->get();

        return (int) $result[0]['count'] > 0;
    }

    public static function pluck(string $field, array $where = []): array
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        $results = $query->select([$field])->get();

        return array_column($results, $field);
    }

    public static function chunk(int $count, callable $callback, array $where = []): void
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        $offset = 0;

        while (true) {
            $results = $query->limit($count)->offset($offset)->get();
            if (empty($results)) {
                break;
            }

            $callback($results);
            $offset += $count;
        }
    }

    public static function avg(string $field, array $where = []): float
    {
        $instance = new static;
        $query = new QueryBuilder(self::$connection);
        $query->table($instance->getTable());

        // Adiciona condições WHERE, se fornecidas
        foreach ($where as $fieldCondition => $value) {
            $query->where($fieldCondition, '=', $value);
        }

        // Passa a média como um array
        $result = $query->select(["AVG($field) as average"])->get();

        return (float) $result[0]['average'] ?? 0.0; // Retorna 0.0 se não houver resultados
    }

    /**
     * Recupera um registro pelo ID.
     *
     * @param mixed $id O ID do registro a ser recuperado.
     * @return array|null O registro encontrado ou null se não encontrado.
     */
    public static function find($id): ?array
    {
        $cacheKey = static::class . ":$id";
        $cachedModel = self::$cacheManager->getModelCache()->get($cacheKey);

        if ($cachedModel) {
            return $cachedModel;
        }

        $instance = new static;
        $result = (new QueryBuilder(self::$connection))->table($instance->getTable())->where($instance->getPrimaryKey(), '=', $id)->get()[0] ?? null;
        
        if ($result) {
            self::$cacheManager->getModelCache()->put($cacheKey, $result);
        }

        return $result;
    }

    /**
     * Salva o modelo no banco de dados.
     *
     * @return bool Verdadeiro se a operação for bem-sucedida, falso caso contrário.
     */
    public function save(): bool
    {
        $query = new QueryBuilder(self::$connection);
        if (isset($this->attributes[$this->primaryKey])) {
            if ($this->timestamps) {
                $this->attributes['updated_at'] = date('Y-m-d H:i:s');
            }
            self::fireEvent('updating', $this);
            $result = $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
                ->update($this->attributes);
            self::fireEvent('updated', $this);
            return $result;
        } else {
            if ($this->timestamps) {
                $this->attributes['created_at'] = date('Y-m-d H:i:s');
            }
            self::fireEvent('creating', $this);
            $result = $query->table($this->getTable())->insert($this->attributes);
            self::fireEvent('created', $this);
            return $result;
        }
    }

    /**
     * Exclui o registro do banco de dados.
     *
     * @return bool Verdadeiro se a operação for bem-sucedida, falso caso contrário.
     */
    public function delete(): bool
    {
        if (isset($this->attributes[$this->primaryKey])) {
            $query = new QueryBuilder(self::$connection);
            if ($this->softDeletes) {
                $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
                self::fireEvent('deleting', $this);
                $result = $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
                    ->update(['deleted_at' => $this->attributes['deleted_at']]);
                self::fireEvent('deleted', $this);
                return $result;
            }
            self::fireEvent('deleting', $this);
            $result = $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])->delete();
            self::fireEvent('deleted', $this);
            return $result;
        }
        return false;
    }

    /**
     * Define um relacionamento um-para-um com outro modelo.
     *
     * @param string $related O nome da classe do modelo relacionado.
     * @param string $foreignKey A chave estrangeira do modelo relacionado.
     * @param string $localKey A chave local do modelo atual (padrão é 'id').
     * @return mixed O registro relacionado.
     */
    public function hasOne(string $related, string $foreignKey, string $localKey = 'id')
    {
        return (new $related)->where($foreignKey, '=', $this->attributes[$localKey])->first();
    }

    /**
     * Define um relacionamento um-para-muitos com outro modelo.
     *
     * @param string $related O nome da classe do modelo relacionado.
     * @param string $foreignKey A chave estrangeira do modelo relacionado.
     * @param string $localKey A chave local do modelo atual (padrão é 'id').
     * @return array Lista de registros relacionados.
     */
    public function hasMany(string $related, string $foreignKey, string $localKey = 'id'): array
    {
        return (new $related)->where($foreignKey, '=', $this->attributes[$localKey])->get();
    }

    /**
     * Define um relacionamento pertence-a com outro modelo.
     *
     * @param string $related O nome da classe do modelo relacionado.
     * @param string $foreignKey A chave estrangeira do modelo atual.
     * @param string $ownerKey A chave do modelo proprietário (padrão é 'id').
     * @return mixed O registro do modelo proprietário.
     */
    public function belongsTo(string $related, string $foreignKey, string $ownerKey = 'id')
    {
        return (new $related)->where($ownerKey, '=', $this->attributes[$foreignKey])->first();
    }

    /**
     * Define um relacionamento has-one-through.
     *
     * @param string $through O nome da classe do modelo intermediário.
     * @param string $related O nome da classe do modelo relacionado.
     * @param string $firstKey A chave do modelo atual.
     * @param string $throughKey A chave do modelo intermediário.
     * @param string $secondKey A chave do modelo relacionado.
     * @return mixed O registro relacionado.
     */
    public function hasOneThrough(string $through, string $related, string $firstKey, string $throughKey, string $secondKey)
    {
        $throughModel = new $through();
        $relatedModel = new $related();

        return $this->hasOne($related, $throughModel->getTable() . '.' . $secondKey, $this->getTable() . '.' . $firstKey);
    }

    /**
     * Define um relacionamento has-many-through.
     *
     * @param string $through O nome da classe do modelo intermediário.
     * @param string $related O nome da classe do modelo relacionado.
     * @param string $firstKey A chave do modelo atual.
     * @param string $throughKey A chave do modelo intermediário.
     * @param string $secondKey A chave do modelo relacionado.
     * @return array Lista de registros relacionados.
     */
    public function hasManyThrough(string $through, string $related, string $firstKey, string $throughKey, string $secondKey)
    {
        $throughModel = new $through();
        $relatedModel = new $related();

        return $this->hasMany($related, $throughModel->getTable() . '.' . $secondKey, $this->getTable() . '.' . $firstKey);
    }

    /**
     * Carrega relacionamentos com eager loading.
     *
     * @param string|array $relations O nome do(s) relacionamento(s) a serem carregados.
     * @return $this
     */
    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = explode(',', $relations);
        }

        foreach ($relations as $relation) {
            $this->loadRelation($relation);
        }

        return $this;
    }

    /**
     * Carrega um relacionamento específico com eager loading.
     *
     * @param string $relation O nome do relacionamento a ser carregado.
     * @return $this
     */
    protected function loadRelation(string $relation)
    {
        $parts = explode('.', $relation);
        $currentModel = $this;
        $query = new QueryBuilder(self::$connection);

        foreach ($parts as $part) {
            $method = $this->getRelationMethod($part);
            $relatedModel = $currentModel->$method($part);
            $currentModel = $relatedModel;
            $query->join(
                $relatedModel->getTable(),
                $relatedModel->getTable() . '.' . $relatedModel->getPrimaryKey(),
                '=',
                $currentModel->getTable() . '.' . $part
            );
        }

        $this->attributes = array_merge(
            $this->attributes,
            $query->table($this->getTable())->where($this->getPrimaryKey(), '=', $this->attributes[$this->getPrimaryKey()])->get()[0]
        );

        return $this;
    }

    /**
     * Determina o método de relacionamento a ser usado.
     *
     * @param string $relation O nome do relacionamento.
     * @return string O nome do método de relacionamento.
     */
    protected function getRelationMethod(string $relation): string
    {
        $methods = [
            'hasOne' => 'hasOne',
            'hasMany' => 'hasMany',
            'belongsTo' => 'belongsTo',
            'belongsToMany' => 'belongsToMany'
        ];

        foreach ($methods as $method => $name) {
            if (method_exists($this, $method)) {
                $reflection = new \ReflectionMethod($this, $method);
                if ($reflection->getNumberOfParameters() === 3) {
                    return $method;
                }
            }
        }

        throw new RelationshipException("Método de relacionamento não encontrado para '$relation'");
    }

    /**
     * Define um relacionamento polimórfico.
     *
     * @param string $name O nome do relacionamento.
     * @param array $morphTypes Um array de nomes de classes relacionadas.
     * @param string $morphId A chave que armazena o ID do modelo relacionado.
     * @param string $morphType A chave que armazena o tipo do modelo relacionado.
     * @return mixed O registro relacionado.
     */
    public function morphOne(string $name, array $morphTypes, string $morphId, string $morphType)
    {
        return $this->hasOne(
            PolymorphicModel::class,
            $morphId,
            $this->getPrimaryKey()
        )->where($morphType, in_array($this::class, $morphTypes) ? $this::class : null);
    }

    /**
     * Define um relacionamento polimórfico de muitos para um.
     *
     * @param string $name O nome do relacionamento.
     * @param array $morphTypes Um array de nomes de classes relacionadas.
     * @param string $morphId A chave que armazena o ID do modelo relacionado.
     * @param string $morphType A chave que armazena o tipo do modelo relacionado.
     * @return array Lista de registros relacionados.
     */
    public function morphMany(string $name, array $morphTypes, string $morphId, string $morphType)
    {
        $queryBuilder = new QueryBuilder(self::$connection);
        return $queryBuilder->table((new PolymorphicModel())->getTable())
            ->where($morphId, '=', $this->attributes[$this->getPrimaryKey()])
            ->whereIn($morphType, $morphTypes)
            ->get();
    }

    /**
     * Obtém o nome da tabela associada ao modelo.
     *
     * @return string Nome da tabela.
     */
    public function getTable(): string
    {
        return $this->table ?? strtolower(static::class);
    }

    /**
     * Obtém a chave primária da tabela associada.
     *
     * @return string Nome da chave primária.
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Converte um valor para o tipo apropriado, baseado nas definições de casting.
     *
     * @param string $key Nome do atributo.
     * @param mixed $value Valor a ser convertido.
     * @return mixed Valor convertido.
     */
    protected function castAttribute(string $key, $value)
    {
        if (isset($this->casts[$key])) {
            switch ($this->casts[$key]) {
                case 'int':
                    return (int) $value;
                case 'float':
                    return (float) $value;
                case 'json':
                    return json_decode($value, true);
                case 'datetime':
                    return new \DateTime($value);
                default:
                    return $value;
            }
        }
        return $value;
    }

    public function belongsToMany(string $related, string $table = null, string $foreignKey = null, string $otherKey = null): BelongsToMany
    {
        $relatedModel = new $related();

        $table = $table ?? $this->getJoiningTableName($this->getTable(), $relatedModel->getTable());
        $foreignKey = $foreignKey ?? strtolower((new \ReflectionClass($this))->getShortName()) . '_' . $this->getPrimaryKey();
        $otherKey = $otherKey ?? strtolower((new \ReflectionClass($relatedModel))->getShortName()) . '_' . $relatedModel->getPrimaryKey();

        return new BelongsToMany($this, $related, $table, $foreignKey, $otherKey, self::$connection);
    }

    protected function getJoiningTableName(string $table1, string $table2): string
    {
        $tables = [$table1, $table2];
        sort($tables);
        return implode('_', $tables);
    }

    /**
     * Converte os atributos do modelo para um array.
     *
     * @return array Atributos do modelo em formato de array.
     */
    public function toArray(): array
    {
        $data = $this->attributes;
        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    /**
     * Converte os atributos do modelo para uma string JSON.
     *
     * @return string Atributos do modelo em formato JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
