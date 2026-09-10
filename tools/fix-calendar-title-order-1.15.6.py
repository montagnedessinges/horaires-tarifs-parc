from pathlib import Path
p=Path('assets/frontend.js')
text=p.read_text()
old="var parkTitle=document.createElement('p');parkTitle.className='parcs-ht-park-hours-title';parkTitle.textContent=parkHoursTitle;box.insertBefore(parkTitle,hours);\n    box.appendChild(hours);"
new="var parkTitle=document.createElement('p');parkTitle.className='parcs-ht-park-hours-title';parkTitle.textContent=parkHoursTitle;box.appendChild(parkTitle);\n    box.appendChild(hours);"
if text.count(old)!=1:
    raise SystemExit('calendar title order marker not found exactly once')
p.write_text(text.replace(old,new,1))
