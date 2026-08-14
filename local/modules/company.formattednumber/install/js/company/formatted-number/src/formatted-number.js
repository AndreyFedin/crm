const instances = new WeakMap();

class FormattedNumberInput
{
	constructor(options)
	{
		this.displayInput = options.displayInput;
		this.valueInput = options.valueInput;
		this.precision = Number.isInteger(options.precision)
			? Math.max(0, Math.min(12, options.precision))
			: 0;
		this.allowNegative = options.allowNegative !== false;

		this.handleInput = this.handleInput.bind(this);
		this.handleBlur = this.handleBlur.bind(this);

		this.displayInput.addEventListener('input', this.handleInput);
		this.displayInput.addEventListener('blur', this.handleBlur);
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
		this.dispatchInput();
	}

	handleBlur()
	{
		const parsed = this.parse(this.displayInput.value);

		if (parsed.empty)
		{
			this.displayInput.value = '';
			this.valueInput.value = '';
			this.dispatchInput();
			this.dispatchChange();
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

		this.dispatchInput();
		this.dispatchChange();
	}

	parse(rawValue)
	{
		let value = String(rawValue ?? '')
			.replace(/\u00A0/g, ' ')
			.replace(/\u202F/g, ' ')
			.trim();

		if (value === '')
		{
			return this.emptyValue();
		}

		const negative = this.allowNegative && value.startsWith('-');

		value = value.replace(/\s+/g, '');
		value = value.replace(/[^\d.,]/g, '');

		if (value === '')
		{
			return this.emptyValue();
		}

		const decimalPosition = this.precision > 0
			? this.findDecimalPosition(value)
			: -1;

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
		const lastDot = value.lastIndexOf('.');
		const lastComma = value.lastIndexOf(',');

		if (lastDot === -1 && lastComma === -1)
		{
			return -1;
		}

		if (lastDot !== -1 && lastComma !== -1)
		{
			return Math.max(lastDot, lastComma);
		}

		const separator = lastDot !== -1 ? '.' : ',';
		const position = value.lastIndexOf(separator);
		const separatorCount = value
			.split('')
			.filter((character) => character === separator)
			.length;

		if (separatorCount === 1)
		{
			return position;
		}

		const digitsAfter = value.length - position - 1;

		return digitsAfter <= this.precision
			? position
			: -1;
	}

	formatDisplay(parsed, normalizePrecision)
	{
		if (parsed.empty)
		{
			return '';
		}

		let integer = parsed.integer || '0';
		integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

		let result = parsed.negative ? '-' : '';
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

			result += '.' + fraction;
		}

		return result;
	}

	formatCanonical(parsed, normalizePrecision)
	{
		if (parsed.empty)
		{
			return '';
		}

		let result = parsed.negative ? '-' : '';
		result += parsed.integer || '0';

		if (this.precision > 0 && parsed.hasDecimal)
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

	emptyValue()
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
		return value
			.substring(0, caretPosition)
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
			if (!/[\s\u00A0\u202F]/.test(value[realPosition]))
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
			this.displayInput.setSelectionRange(realPosition, realPosition);
		}
		catch (e)
		{
		}
	}

	dispatchInput()
	{
		this.valueInput.dispatchEvent(
			new Event('input', {bubbles: true})
		);
	}

	dispatchChange()
	{
		this.valueInput.dispatchEvent(
			new Event('change', {bubbles: true})
		);
	}

	destroy()
	{
		this.displayInput.removeEventListener('input', this.handleInput);
		this.displayInput.removeEventListener('blur', this.handleBlur);
		instances.delete(this.displayInput);
	}
}

export function init(options)
{
	const displayInput = document.getElementById(options.displayInputId);
	const valueInput = document.getElementById(options.valueInputId);

	if (!displayInput || !valueInput)
	{
		return null;
	}

	if (instances.has(displayInput))
	{
		return instances.get(displayInput);
	}

	const instance = new FormattedNumberInput({
		displayInput,
		valueInput,
		precision: Number(options.precision ?? 0),
		allowNegative: options.allowNegative !== false,
	});

	instances.set(displayInput, instance);

	return instance;
}
