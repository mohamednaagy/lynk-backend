<?php

return [
    /*
     |--------------------------------------------------------------------------
     | Validation Language Lines
     |--------------------------------------------------------------------------
     |
     | The following language lines contain the default error messages used by
     | the validator class. Some of these rules have multiple versions such
     | as the size rules. Feel free to tweak each of these messages here.
     |
     */
    'unique_input' => 'This value already exists',
    'accepted' => 'The :attribute must be accepted.',
    'accepted_if' => 'The :attribute must be accepted when :other is :value.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute must only contain letters.',
    'alpha_dash' => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute must only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute must have between :min and :max items.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute must be between :min and :max.',
        'string' => 'The :attribute must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'declined' => 'The :attribute must be declined.',
    'declined_if' => 'The :attribute must be declined when :other is :value.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute must have more than :value items.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'numeric' => 'The :attribute must be greater than :value.',
        'string' => 'The :attribute must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute must have :value items or more.',
        'file' => 'The :attribute must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be greater than or equal to :value.',
        'string' => 'The :attribute must be greater than or equal to :value characters.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'array' => 'The :attribute must have less than :value items.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'numeric' => 'The :attribute must be less than :value.',
        'string' => 'The :attribute must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute must not have more than :value items.',
        'file' => 'The :attribute must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute must be less than or equal to :value.',
        'string' => 'The :attribute must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute must not have more than :max items.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute must not be greater than :max.',
        'string' => 'The :attribute must not be greater than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute must have at least :min items.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'numeric' => 'The :attribute must be at least :min.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'multiple_of' => 'The :attribute must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'present' => 'The :attribute field must be present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'array' => 'The :attribute must contain :size items.',
        'file' => 'The :attribute must be :size kilobytes.',
        'numeric' => 'The :attribute must be :size.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute must be a valid URL.',
    'uuid' => 'The :attribute must be a valid UUID.',
    'national_id_wrong_format' => ':attribute format is not valid',
    'host_whitelist' => 'The :attribute is not whitelisted.',
    'phone' => 'The :attribute is not a valid phone number.',
    'webhook_type_limit' => 'This webhook couldn\'t be used more than :limit time(s)',
    'money_value' => 'The :attribute format is invalid.',
    'captcha' => 'The :attribute verification failed.',
    'company_unique_name' => 'Identifier should contain only English letters, numbers and _. It should start with English letter',

    'url_protocol' => 'The :attribute must start with one of the following URL protocols: :values.',
    'amount_not_multiples_of_order_cost' => 'Entered amount should be multiples of :order_cost_with_vat',
    'custom_validation' => [],
    /*
     |--------------------------------------------------------------------------
     | Custom Validation Language Lines
     |--------------------------------------------------------------------------
     |
     | Here you may specify custom validation messages for attributes using the
     | convention "attribute.rule" to name the lines. This makes it quick to
     | specify a specific custom language line for a given attribute rule.
     |
     */

    'custom' => [
        'company_unique_name' => [
            'regex' => 'Identifier should contain only English letters, numbers and _. It should start with English letter',
        ],
        'commodity' => [
            'regex' => 'Identifier should contain only English letters, numbers and _. It should start with English letter',
        ],
        'location' => [
            'regex' => '_ صيغه معرف السوق غير صحيحه يجب ان تكون حروف او ارقام او ',
        ],
        'order_cost_amount_tiers_range' => 'Order value start must be greater than order value end of previous tier with only 0.01',
    ],
    /*
     |--------------------------------------------------------------------------
     | Custom Validation Attributes
     |--------------------------------------------------------------------------
     |
     | The following language lines are used to swap our attribute placeholder
     | with something more reader friendly such as "E-Mail Address" instead
     | of "email". This simply helps us make our message more expressive.
     |
     */

    'attributes' => [
        'company_unique_name' => 'company identifier',
        'company_cr' => 'company CR',
        'national_id' => 'national ID',
        'invalid_case_proceed' => 'The case value entered is invalid for this trade request',
        'document_type' => 'document type',
        'context' => 'context',
        'trader_order_id' => 'trader order ID',
        'transaction_id' => 'transaction ID',

    ],
    'max_digits' => 'It must contain 10 numbers',
    'field_is_required' => 'This field is required',
    'max_string_chars' => 'This field cannot be more than :max characters long',
    'field_should_be_boolean' => 'This field only accepts the values true or false.',
    'periods_overlapped' => 'Periods must not overlap with existing ranges',
    'effective_end_after_or_equal_start' => 'The end date must be the same as or after the start date.',
    'invalid_date_format' => 'Invalid date format.',
    'field_is_not_editable' => 'This Field Is Not Editable',
    'periods_overlapped_with_existing' => 'Periods must not overlap with existing ranges',
    'select_commodity_not_valid' => 'The selected commodity type is not active or does not exist for the specified provider',
    'supporting_document_required' => 'Supporting document is required.',
    'supporting_document_must_be_file' => 'Supporting document must be a file.',
    'supporting_document_must_be_pdf' => 'Supporting document must be a PDF file.',
    'supporting_document_max_size' => 'Supporting document size must not exceed 10MB.',
    'client_invalid_for_lender' => 'The specified client does not belong to the given lender.',
    'auto_sell_period_invalid_for_client' => 'The specified auto sell period does not belong to the given client.',
    'media_file_required' => 'A file is required when media is provided.',
    'media_file_must_be_file' => 'The media file must be a valid file.',
    'media_file_must_be_pdf' => 'The media file must be a PDF document.',
    'media_file_max_size' => 'The media file may not be greater than 10 MB.',
    'media_type_required' => 'A media type is required when media is provided.',
    'media_type_invalid' => 'The selected media type is invalid.',
    'media_or_delete_required' => 'You must either provide media files or choose to delete existing media.',
    'greater_than_zero' => 'Must be greater than zero',
    'only_english_alpha_numbers_underscore_hyphen_allowed' => 'Only english alpha/numbers/underscore/hyphen allowed',
    'no_hyphen_at_start' => 'hyphen cannot be used at the beginning',
    'pdf' => [
        'document_type_required' => 'Document type is required. Please provide a valid document type.',
        'document_type_string' => 'Document type must be a string.',
        'document_type_invalid' => 'Invalid document type provided. Please check the available document types.',
        'context_required' => 'Context object is required.',
        'context_array' => 'Context must be an object.',
        'trader_order_id_integer' => 'Trader order ID must be a valid integer.',
        'trader_order_not_found' => 'Trader order not found.',
        'transaction_id_integer' => 'Transaction ID must be a valid integer.',
        'transaction_not_found' => 'Transaction not found.',
        'trader_order_required' => "Document type ':document_type' requires a trader order ID",
        'transaction_required' => "Document type ':document_type' requires a transaction ID",
        'context_required_field' => 'At least one context field (trader_order_id or transaction_id) must be provided',
    ],
];
