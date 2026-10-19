# Fonts embedded in PDFs

- Hind Siliguri — SIL Open Font License 1.1, © Indian Type Foundry (github.com/google/fonts, ofl/hindsiliguri). Chosen because mPDF fails to parse the current Noto Bengali builds ("Undefined array key" in TTFontFile).
- Arabic text uses XB Riyaz, which ships inside mPDF (`vendor/mpdf/mpdf/ttfonts`). Amiri was tried first but mPDF cannot parse its GPOS tables.

Used by `Modules\Core\Services\PdfService` (mPDF, OpenType layout on so Bangla conjuncts shape correctly).
