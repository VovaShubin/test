<?php

use yii\db\Migration;

class m130524_201442_init extends Migration
{
    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%user}}', [
            'id' => $this->primaryKey(),
            'username' => $this->string()->notNull()->unique(),
            'auth_key' => $this->string(32)->notNull(),
            'password_hash' => $this->string()->notNull(),
            'password_reset_token' => $this->string()->unique(),
            'email' => $this->string()->notNull()->unique(),

            'status' => $this->smallInteger()->notNull()->defaultValue(10),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
			'access_token' => $this->integer()->notNull()->defaultValue(123),
        ], $tableOptions);

		$this->createTable('{{%authors}}', [
			'id' => $this->primaryKey(),
			'author' => $this->string(),
		], $tableOptions);

		$this->createTable('{{%authors_books}}', [
			'id' => $this->primaryKey(),
			'author' => $this->integer(),
			'books' => $this->integer(),
			'PRIMARY KEY (`author`, `books`)'
		], $tableOptions);

		$this->createTable('{{%books}}', [
			'id' => $this->primaryKey(),
			'name' => $this->string(),
			'years' => $this->string(),
			'books' => $this->integer(),
			'description' => $this->string(),
			'isbn' => $this->string(),
			'img' => $this->string(),
		], $tableOptions);

		$this->createTable('{{%follower}}', [
			'id' => $this->primaryKey(),
			'author' => $this->integer(),
			'phone' => $this->string(),
			'PRIMARY KEY (`author`, `phone`)'
		], $tableOptions);

	}


    public function down()
    {
        $this->dropTable('{{%user}}');
		$this->dropTable('{{%authors}}');
		$this->dropTable('{{%authors_books}}');
		$this->dropTable('{{%books}}');
		$this->dropTable('{{%follower}}');
    }
}
