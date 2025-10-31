<?php
    class Alert {
        private bool $success; // BOOL
        private string $type; // 
        private ?string $message; //
        private null|array|Model $data; //
        private $language;
        public const TYPE_SUCCESS = "success";
        public const TYPE_ERROR = "error";
        public const TYPE_DANGER = "danger";
        public const TYPE_WARNING = "warning";
        public const TYPE_INFO = "info";
        public const TYPE_DEFAULT = "default";
        public const TYPE_INVISIBLE = "invisible";

        public function __construct(bool $success, string $type, ?string $message = NULL, null|array|Model $data = NULL) {
            $this->setSuccess($success) ;
            $this->setType($type);
            $this->setMessage($message) ;
            $this->setData($data) ;
        }

        /**
         * Get the value of success
         */
        public function getSuccess() : bool
        {
            return (bool) $this->success;
        }

        /**
         * Set the value of success
         */
        public function setSuccess(bool $success): self
        {
            $this->success = $success;
            return $this;
        }

        /**
         * Get the value of type
         */
        public function getType() : string
        {
            return htmlspecialchars($this->type);
        }

        /**
         * Set the value of type
         */
        public function setType(string $type): self
        {
            $this->type = $type;
            return $this;
        }

        /**
         * Get the value of message
         */
        public function getMessage() : string
        {
            return htmlspecialchars($this->message);
        }

        /**
         * Set the value of message
         */
        public function setMessage(string $message): self
        {
            $this->message = (@$this->language[trim($message)] ?: $message);
            return $this;
        }
        
        /**
         * Get the value of data
         */
        public function getData() : ?array
        {
            return $this->data;
        }

        /**
         * Set the value of data
         */
        public function setData(null|array|Model $data) : self
        {
            $this->data = $data;
            return $this;
        }

        public function asArray() {
            $alert =["type" => $this->type, "success" => $this->success, "message"=> $this->message];
            return ($this->data != null ? array_merge($alert, ["data"=>$this->data]) : $alert);
        }

        public function asJson() {
            return json_encode($this->asArray());
        }

        public function asHtml() {
            return ($this->type == self::TYPE_INVISIBLE ? '' : '<div class="alert alert-'.($this->getType() == self::TYPE_ERROR ? "danger" : $this->getType() ).'">'.$this->getMessage().'</div>');
        }

    }
?>