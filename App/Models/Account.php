<?php
	class Account extends ObjectModel {
		public string $id;
		public string $email;
		private string $password;
		public string $username;
		public ?array $data;
		public ?string $contacts_id;
		private ?string $safety_level;
		private ?string $note;
		private float $balance = 0;
		private $__contact;
		private string $created_at;
		private string $updated_at;

		/**
		 * Get the value of id
		 */
		public function getId(): ?string
		{
			return $this->id === null ? null : htmlspecialchars($this->id ?? "");
		}

		/**
		 * Set the value of id
		 */
		public function setId(?string $id): self
		{
			$this->id = $id;

			return $this;
		}

		/**
		 * Get the value of username
		 */
		public function getUsername(): ?string
		{
			return $this->username === null ? null : htmlspecialchars($this->username ?? "");
		}

		/**
		 * Set the value of username
		 */
		public function setUsername(?string $username): self
		{
			$this->username = $username;

			return $this;
		}

		/**
		 * Get the value of email
		 */
		public function getEmail(): string
		{
			return $this->email === null ? null : htmlspecialchars($this->email);
		}

		/**
		 * Set the value of email
		 */
		public function setEmail(string $email): self
		{
			if ($email !== null) {
				$email = filter_var($email, FILTER_SANITIZE_EMAIL);
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					throw new Exception(_e("Adresse email invalide."), 400);
				}
			}
			$this->email = $email;
			return $this;
		}

		/**
		 * Get the value of password
		 */
		public function getPassword(): string
		{
			return htmlspecialchars($this->password);
		}

		/**
		 * Set the value of password
		 */
		public function setPassword(string $password, $encrypt = false): self
		{
			if($encrypt == true) $password=password_hash($password, PASSWORD_BCRYPT);
			$this->password = $password;
			return $this;
		}

		/**
		 * Get the value of role
		 */
		public function getRole(): int
		{
			return $this->role === null ? null : (int) $this->role;
		}

		/**
		 * Set the value of role
		 */
		public function setRole(int $role): self
		{
			$this->role = $role;

			return $this;
		}

		/**
		 * Get the value of contacts_id
		 */
		public function getContactsId()
		{
			return $this->contacts_id === null ? null : htmlspecialchars($this->contacts_id);
		}

		/**
		 * Set the value of contacts_id
		 */
		public function setContactsId(?string $contacts_id): self
		{
			$this->contacts_id = $contacts_id;

			return $this;
		}

		/**
		 * Get the value of contact
		 */
		public function getContact(): ?Contact
		{
			return $this->__contact === null ? null : $this->__contact;
		}

		/**
		 * Set the value of contact
		 */
		public function setContact(?Contact $contact): self
		{
			$this->__contact = $contact;

			return $this;
		}

		/**
		 * Get the value of safety_level
		 */
		public function getSafetyLevel()
		{
			return $this->safety_level === null ? null : htmlspecialchars($this->safety_level ?? "");
		}

		/**
		 * Set the value of safety_level
		 */
		public function setSafetyLevel($safety_level): self
		{
			$this->safety_level = $safety_level;

			return $this;
		}

		/**
		 * Get the value of note
		 */
		public function getNote()
		{
			return $this->note === null ? null : htmlspecialchars($this->note ?? "");
		}

		/**
		 * Set the value of note
		 */
		public function setNote($note): self
		{
			$this->note = $note;

			return $this;
		}

		/**
		 * Get the value of balance
		 */
		public function getBalance()
		{
			return format_money((float) $this->balance, false);
		}

		/**
		 * Set the value of balance
		 */
		public function setBalance($balance): self
		{
			$this->balance = $balance;

			return $this;
		}

		public function getData(): ?AccountData
		{
			return $this->data;
		}

		/**
		 * Set the value of data
		 */
		public function setData(null | AccountData | array | string $data): self
		{
			if (is_string($data)) {
				$data = json_decode($data, true);
			}

			if (is_array($data)) {
				$data = new AccountData($data);
			}

			$this->data = $data;
			//if($this->exist()) $this->update();
			return $this;
		}


		/**
		 * Get the value of created_at
		 */
		public function getCreatedAt()
		{
				return $this->created_at;
		}

		/**
		 * Set the value of created_at
		 */
		public function setCreatedAt($created_at): self
		{
				$this->created_at = $created_at;

				return $this;
		}

		/**
		 * Get the value of updated_at
		 */
		public function getUpdatedAt()
		{
				return $this->updated_at;
		}

		/**
		 * Set the value of updated_at
		 */
		public function setUpdatedAt($updated_at): self
		{
				$this->updated_at = $updated_at;

				return $this;
		}
	}
