# Pronouns

Pronoun field with opinionated formatting. Supports displaying gender/pronoun field on member tooltip/view and postbit.

Extends the 'gender' field with a number of options;
- Genderfluid
- Nonbinary
- Agender
- Other
- Prefer not to say

Adds "Pronouns" field, with a custom validator which standardizes various formatting to something similar to;
- He or him-his => He/Him/His
- her|she\her => Her/She/Her
- they_them => They/Them

Duplicates are stripped, and the user is prevented from putting their username in this field.

Validator options:
- The default validator method "listValidatorEn4", enforces "english only", with at most 4 options.
- To allow all unicode characters set the PHP callback method to "listValidator4"
- To allow 3 options set the PHP callback method to "listValidatorEn3"
