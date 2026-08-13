const instances = new WeakMap();

class NumberInput
{
	constructor(options)
	{
		this.displayInput = options.displayInput;
		this.valueInput = options.valueInput;

		this.precision = Number.isInteger(options.precision)
			? Math.max(0, options.precision)
			: 2;

		this.allowNegative = options.allowNegative !== false;

		this.handleInput = this.handleInput.bind(this);
		this.handleBlur = this.handleBlur.bind(this);

		this.bindEvents();
	}

	bindEvents()
	{
		this.displayInput.addEventListener('input', this.handleInput);
		this.displayInput.addEventListener('blur', this.handleBlur);
	}

	destroy()
	{
		this.displayInput.removeEventListener('input', this.handleInput);
		this.displayInput.removeEventListener('blur', this.handleBlur);

		instances.delete(this.displayInput);
	}

	handleInput()
	{
		const caretPosition = this.displayInput.selectionStart ?? 0;

		const logicalCaretPosition = this.getLogicalCaretPosition(
			this.displayInput.value,
			caretPosition
		);

		const parsed = this.parse(this.displayInput.value);

		this.displayInput.value = this.formatDisplay(parsed, false);
		this.valueInput.value = this.formatCanonical(parsed, false);

		this.restoreCaretPosition(logicalCaretPosition);

		this.dispatchInputEvent();
	}

	handleBlur()
	{
		const parsed = this.parse(this.displayInput.value);

		if (parsed.empty)
		{
			this.displayInput.value = '';
			this.valueInput.value = '';

			this.dispatchInputEvent();
			this.dispatchChangeEvent();

			return;
		}

		if (this.precision > 0)
		{
			parsed.hasDecimal = true;

			parsed.fraction = parsed.fraction
				.padEnd(this.precision, '0')
				.substring(0, this.precision);
		}
		else
		{
			parsed.hasDecimal = false;
			parsed.fraction = '';
		}

		this.displayInput.value = this.formatDisplay(parsed, true);
		this.valueInput.value = this.formatCanonical(parsed, true);

		this.dispatchInputEvent();
		this.dispatchChangeEvent();
	}

	parse(rawValue)
	{
		let value = String(rawValue ?? '');

		value = value
			.replace(/\u00A0/g, ' ')
			.replace(/\u202F/g, ' ')
			.trim();

		if (value === '')
		{
			return this.getEmptyValue();
		}

		const negative = this.allowNegative && /^\s*-/.test(value);

		value = value.replace(/\s+/g, '');
		value = value.replace(/[^\d.,]/g, '');

		if (value === '')
		{
			return this.getEmptyValue();
		}

		let decimalPosition = -1;

		if (this.precision > 0)
		{
			decimalPosition = this.findDecimalPosition(value);
		}

		let integer = '';
		let fraction = '';
		let hasDecimal = false;

		if (decimalPosition >= 0)
		{
			hasDecimal = true;

			integer = value
				.substring(0, decimalPosition)
				.replace(/[.,]/g, '');

			fraction = value
				.substring(decimalPosition + 1)
				.replace(/[.,]/g, '')
				.replace(/\D/g, '')
				.substring(0, this.precision);
		}
		else
		{
			integer = value.replace(/[.,]/g, '');
		}

		integer = integer.replace(/\D/g, '');
		integer = integer.replace(/^0+(?=\d)/, '');

		if (integer === '' && hasDecimal)
		{
			integer = '0';
		}

		return {
			empty: integer === '' && !hasDecimal,
			negative,
			integer,
			fraction,
			hasDecimal,
		};
	}

	findDecimalPosition(value)
	{
		const lastComma = value.lastIndexOf(',');
		const lastDot = value.lastIndexOf('.');

		if (lastComma === -1 && lastDot === -1)
		{
			return -1;
		}

		if (lastComma !== -1 && lastDot !== -1)
		{
			return Math.max(lastComma, lastDot);
		}

		const separator = lastComma !== -1 ? ',' : '.';
		const position = value.lastIndexOf(separator);

		const count = value
			.split('')
			.filter((character) => character === separator)
			.length;

		if (count === 1)
		{
			return position;
		}

		const digitsAfterSeparator = value.length - position - 1;

		if (
			digitsAfterSeparator >= 0
			&& digitsAfterSeparator <= this.precision
		)
		{
			return position;
		}

		return -1;
	}

	formatDisplay(parsed, normalizePrecision = false)
	{
		if (parsed.empty)
		{
			return '';
		}

		let integer = parsed.integer || '0';

		integer = integer.replace(
			/\B(?=(\d{3})+(?!\d))/g,
			' '
		);

		let result = '';

		if (parsed.negative)
		{
			result += '-';
		}

		result += integer;

		if (this.precision > 0 && parsed.hasDecimal)
		{
			let fraction = parsed.fraction;

			if (normalizePrecision)
			{
				fraction = fraction
					.padEnd(this.precision, '0')
					.substring(0, this.precision);
			}

			result += ',' + fraction;
		}

		return result;
	}

	formatCanonical(parsed, normalizePrecision = false)
	{
		if (parsed.empty)
		{
			return '';
		}

		let result = '';

		if (parsed.negative)
		{
			result += '-';
		}

		result += parsed.integer || '0';

		if (
			this.precision > 0
			&& parsed.hasDecimal
		)
		{
			let fraction = parsed.fraction;

			if (normalizePrecision)
			{
				fraction = fraction
					.padEnd(this.precision, '0')
					.substring(0, this.precision);
			}

			if (fraction !== '')
			{
				result += '.' + fraction;
			}
		}

		return result;
	}

	getEmptyValue()
	{
		return {
			empty: true,
			negative: false,
			integer: '',
			fraction: '',
			hasDecimal: false,
		};
	}

	getLogicalCaretPosition(value, caretPosition)
	{
		const prefix = value.substring(0, caretPosition);

		return prefix
			.replace(/[\s\u00A0\u202F]/g, '')
			.length;
	}

	restoreCaretPosition(logicalPosition)
	{
		const value = this.displayInput.value;

		let logicalCounter = 0;
		let realPosition = 0;

		for (; realPosition < value.length; realPosition++)
		{
			const character = value[realPosition];

			if (!/[\s\u00A0\u202F]/.test(character))
			{
				logicalCounter++;
			}

			if (logicalCounter >= logicalPosition)
			{
				realPosition++;
				break;
			}
		}

		try
		{
			this.displayInput.setSelectionRange(
				realPosition,
				realPosition
			);
		}
		catch (e)
		{
		}
	}

	dispatchInputEvent()
	{
		this.valueInput.dispatchEvent(
			new Event(
				'input',
				{
					bubbles: true,
				}
			)
		);
	}

	dispatchChangeEvent()
	{
		this.valueInput.dispatchEvent(
			new Event(
				'change',
				{
					bubbles: true,
				}
			)
		);
	}
}

export function init(options)
{
	const displayInput = document.getElementById(
		options.displayInputId
	);

	const valueInput = document.getElementById(
		options.valueInputId
	);

	if (!displayInput || !valueInput)
	{
		return null;
	}

	const existingInstance = instances.get(displayInput);

	if (existingInstance)
	{
		return existingInstance;
	}

	const instance = new NumberInput({
		displayInput,
		valueInput,
		precision: Number(options.precision ?? 2),
		allowNegative: options.allowNegative !== false,
	});

	instances.set(displayInput, instance);

	return instance;
}
