@once
    @push('scripts')
        <script>
            function standardFormValidator(rules, serverErrors = {}) {
                return {
                    rules,
                    errors: Object.fromEntries(Object.entries(serverErrors).map(([field, messages]) => [field, messages[0]])),

                    fieldClass(field) {
                        return this.errors[field]
                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500'
                            : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500';
                    },

                    error(field) {
                        return this.errors[field] || '';
                    },

                    clear(field) {
                        delete this.errors[field];
                    },

                    validate(data) {
                        this.errors = {};

                        Object.entries(this.rules).forEach(([field, fieldRules]) => {
                            const message = this.validateField(field, data[field], data, fieldRules);

                            if (message) {
                                this.errors[field] = message;
                            }
                        });

                        return Object.keys(this.errors).length === 0;
                    },

                    validateField(field, value, data, fieldRules = this.rules[field] || []) {
                        const label = this.label(field);
                        const normalizedValue = value === null || value === undefined ? '' : String(value).trim();
                        const hasNumericRule = fieldRules.includes('numeric');

                        for (const rule of fieldRules) {
                            if (rule === 'required' && normalizedValue === '') {
                                return `${label} is required.`;
                            }

                            if (rule.startsWith('required_if:')) {
                                const [, params] = rule.split(':');
                                const [otherField, expectedValue] = params.split(',');

                                if (data[otherField] === expectedValue && normalizedValue === '') {
                                    return `${label} is required.`;
                                }
                            }

                            if (rule === 'email' && normalizedValue !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(normalizedValue)) {
                                return `${label} must be a valid email address.`;
                            }

                            if (rule === 'numeric' && normalizedValue !== '' && Number.isNaN(Number(normalizedValue))) {
                                return `${label} must be a number.`;
                            }

                            if (rule.startsWith('min:') && normalizedValue !== '') {
                                const min = Number(rule.slice(4));

                                if (hasNumericRule && Number(normalizedValue) < min) {
                                    return `${label} is too small.`;
                                }

                                if (!hasNumericRule && normalizedValue.length < min) {
                                    return `${label} must be at least ${min} characters.`;
                                }
                            }

                            if (rule.startsWith('max:') && normalizedValue !== '') {
                                const max = Number(rule.slice(4));

                                if (!hasNumericRule && normalizedValue.length > max) {
                                    return `${label} may not be greater than ${max} characters.`;
                                }

                                if (hasNumericRule && Number(normalizedValue) > max) {
                                    return `${label} is too large.`;
                                }
                            }

                            if (rule.startsWith('in:') && normalizedValue !== '') {
                                const allowed = rule.slice(3).split(',');

                                if (!allowed.includes(normalizedValue)) {
                                    return `${label} is invalid.`;
                                }
                            }
                        }

                        return '';
                    },

                    label(field) {
                        return field
                            .replaceAll('_', ' ')
                            .replace(/\b\w/g, character => character.toUpperCase());
                    },
                };
            }
        </script>
    @endpush
@endonce
