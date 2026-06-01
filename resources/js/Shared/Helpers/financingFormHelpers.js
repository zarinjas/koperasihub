export function parseOptions(field) {
  try {
    return typeof field.options_json === 'string'
      ? JSON.parse(field.options_json)
      : (field.options_json ?? []);
  } catch {
    return [];
  }
}

export function parseSettings(field) {
  try {
    return typeof field.settings_json === 'string'
      ? JSON.parse(field.settings_json)
      : (field.settings_json ?? {});
  } catch {
    return {};
  }
}

export function getFieldFileUrl(field) {
  const s = parseSettings(field);
  if (field.file_url) return field.file_url;
  if (s.file_path) return '/storage/' + s.file_path;
  return null;
}

export function isRepeaterRows(value) {
  return Array.isArray(value)
    && value.some((row) => row && typeof row === 'object' && !Array.isArray(row));
}

export function getFieldLabel(field) {
  return field.label || '';
}

export function getTypeLabel(type) {
  const labels = {
    short_text: 'Teks', long_text: 'Teks Panjang', email: 'E-mel',
    phone: 'Telefon', identity_no: 'Kad Pengenalan', number: 'Nombor',
    currency: 'Wang (RM)', date: 'Tarikh', select: 'Dropdown',
    radio: 'Radio', checkbox: 'Checkbox', yes_no: 'Ya/Tidak',
    repeater_table: 'Jadual', file: 'Fail', rich_text: 'Kandungan',
    image: 'Imej', pdf_document: 'PDF', note: 'Nota',
    instruction_text: 'Arahan', document_checklist: 'Checklist',
    signature_block: 'Tandatangan', address_my: 'Alamat',
    digital_signature: 'Ttd Digital',
    member_name: 'Nama Ahli', member_identity_no: 'KP Ahli',
    member_dob: 'T.Lahir', member_phone: 'Tel Ahli',
    member_email: 'E-mel Ahli', member_position: 'Jawatan',
    member_employer: 'Majikan', member_member_no: 'No Ahli',
    member_employment_no: 'No Pekerja', member_bank: 'Bank',
    member_bank_account: 'Akaun Bank', member_marital_status: 'Status',
    address_spouse: 'Alamat Pasangan', address_beneficiary: 'Alamat Waris',
  };
  return labels[type] || type;
}
