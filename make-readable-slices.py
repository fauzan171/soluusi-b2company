from pathlib import Path
from PIL import Image


SOURCE = Path("storage/screenshots")
OUTPUT = SOURCE / "readable-v2"
PAGES = ("landing", "klinik", "sekolah", "perusahaan")
SLICE_HEIGHT = 1200


OUTPUT.mkdir(parents=True, exist_ok=True)

for page_name in PAGES:
    pdf_path = OUTPUT / f"{page_name}-versi-baca.pdf"
    if pdf_path.exists():
        print(f"SKIP {page_name}: sudah selesai")
        continue

    image = Image.open(SOURCE / f"{page_name}.png").convert("RGB")
    page_output = OUTPUT / page_name
    page_output.mkdir(parents=True, exist_ok=True)
    slices = []

    for index, top in enumerate(range(0, image.height, SLICE_HEIGHT), start=1):
        bottom = min(top + SLICE_HEIGHT, image.height)
        page_slice = image.crop((0, top, image.width, bottom))
        slice_path = page_output / f"{index:02d}-{page_name}.png"
        page_slice.save(slice_path, optimize=True)
        slices.append(page_slice)

    slices[0].save(
        pdf_path,
        "PDF",
        save_all=True,
        append_images=slices[1:],
        resolution=144,
        quality=92,
    )
    print(f"OK {page_name}: {len(slices)} potongan + PDF")
