<?php
    class AccountData extends ObjectModel {
        public array $preferences = [];
        public ?string $language = "fr";
        public float $load_maximum_amount = 100;
        public float $load_minimum_amount = 1;

        public ?string $otp_secret = null;
        public bool $otp_enabled = false;

        public bool $is_verified = false;
        public ?string $verified_at = null;
        public ?string $verification_token = null;
        public ?string $verification_fingerprint = null;
        public ?string $verification_expire_at = null;



        /**
         * Get the value of preferences
         */
        public function getPreferences()
        {
            return $this->preferences === null ? null : htmlspecialchars($this->preferences);
        }

        /**
         * Set the value of preferences
         */
        public function setPreferences($preferences): self
        {
            $this->preferences = $preferences;
            return $this;
        }

        /**
         * Get the value of language
         */
        public function getLanguage()
        {
                return $this->language === null ? null :htmlspecialchars($this->language);
        }

        /**
         * Set the value of language
         */
        public function setLanguage($language): self
        {
                $this->language = $language;

                return $this;
        }

        /**
         * Get the value of load_maximum_amount
         */
        public function getLoadMaximumAmount()
        {
                return format_money((float) $this->load_maximum_amount, false);
        }

        /**
         * Set the value of load_maximum_amount
         */
        public function setLoadMaximumAmount($load_maximum_amount): self
        {
                $this->load_maximum_amount = $load_maximum_amount;

                return $this;
        }

        /**
         * Get the value of load_minimum_amount
         */
        public function getLoadMinimumAmount()
        {
                return format_money((float) $this->load_minimum_amount, false);
        }

        /**
         * Set the value of load_minimum_amount
         */
        public function setLoadMinimumAmount($load_minimum_amount): self
        {
                $this->load_minimum_amount = $load_minimum_amount;

                return $this;
        }

        /**
         * Get the value of is_verified
         */
        public function getIsVerified()
        {
                return (bool) $this->is_verified;
        }

        /**
         * Set the value of is_verified
         */
        public function setIsVerified(bool|int $is_verified): self
        {
                $this->is_verified = (bool) $is_verified;

                return $this;
        }

        /**
         * Get the value of verified_at
         */
        public function getVerifiedAt() : ?string
        {
            return $this->verified_at === null ? null : htmlspecialchars($this->verified_at);
        }

        /**
         * Set the value of verified_at
         */
        public function setVerifiedAt(?string $verified_at): self
        {
                $this->verified_at = $verified_at;

                return $this;
        }

        /**
         * Get the value of verification_token
         */
        public function getVerificationToken()
        {
                return $this->verification_token === null ? null : htmlspecialchars($this->verification_token);
        }

        /**
         * Set the value of verification_token
         */
        public function setVerificationToken(?string $verification_token): self
        {
                $this->verification_token = $verification_token;

                return $this;
        }

        /**
         * Get the value of verification_fingerprint
         */
        public function getVerificationFingerprint()
        {
                return $this->verification_fingerprint === null ? null : htmlspecialchars($this->verification_fingerprint);
        }

        /**
         * Set the value of verification_fingerprint
         */
        public function setVerificationFingerprint(?string $verification_fingerprint): self
        {
                $this->verification_fingerprint = $verification_fingerprint;

                return $this;
        }

        /**
         * Get the value of verification_expire_at
         */
        public function getVerificationExpireAt()
        {
                return $this->verification_expire_at === null ? null : htmlspecialchars($this->verification_expire_at);
                
        }

        /**
         * Set the value of verification_expire_at
         */
        public function setVerificationExpireAt(?string $verification_expire_at): self
        {
                $this->verification_expire_at = $verification_expire_at;

                return $this;
        }

        /**
         * Get the value of otp_secret
         */
        public function getOtpSecret()
        {
                return $this->otp_secret;
        }

        /**
         * Set the value of otp_secret
         */
        public function setOtpSecret($otp_secret): self
        {
                $this->otp_secret = $otp_secret;

                return $this;
        }

        /**
         * Get the value of otp_enabled
         */
        public function getOtpEnabled()
        {
                return (bool) $this->otp_enabled;
        }

        /**
         * Set the value of otp_enabled
         */
        public function setOtpEnabled($otp_enabled): self
        {
                $this->otp_enabled = $otp_enabled;

                return $this;
        }
    }
?>