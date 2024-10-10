<?php

namespace Vest\ORM;

use PDO;

class Model
{
    // Conexão PDO estática para acesso ao banco de dados
    protected static PDO $connection;

    // Nome da tabela associada ao modelo
    protected string $table;

    // Chave primária da tabela
    protected string $primaryKey = 'id';

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

    /**
     * Construtor que preenche os atributos iniciais do modelo.
     *
     * @param array $attributes Atributos a serem preenchidos no modelo.
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Define a conexão PDO a ser usada pelos modelos.
     *
     * @param PDO $connection Instância de conexão PDO.
     */
    public static function setConnection(PDO $connection)
    {
        self::$connection = $connection;
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
        $instance = new static;
        return (new QueryBuilder(self::$connection))->table($instance->getTable())->get();
    }

    /**
     * Recupera um registro pelo ID.
     *
     * @param mixed $id O ID do registro a ser recuperado.
     * @return array|null O registro encontrado ou null se não encontrado.
     */
    public static function find($id): ?array
    {
        $instance = new static;
        return (new QueryBuilder(self::$connection))->table($instance->getTable())->where($instance->getPrimaryKey(), '=', $id)->get()[0] ?? null;
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
            return $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
                ->update($this->attributes);
        } else {
            if ($this->timestamps) {
                $this->attributes['created_at'] = date('Y-m-d H:i:s');
            }
            return $query->table($this->getTable())->insert($this->attributes);
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
                return $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
                    ->update(['deleted_at' => $this->attributes['deleted_at']]);
            }
            return $query->table($this->getTable())->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])->delete();
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