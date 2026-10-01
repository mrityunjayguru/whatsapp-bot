import os

fpath = r'c:\xampp\htdocs\chatbot\resources\views\contacts\show.blade.php'

if os.path.exists(fpath):
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Extract SECTION 6
    import re
    sec6_pattern = re.compile(r'(\s*<!-- SECTION 6: LOCATION -->\s*<div class="card w-100 grid-margin">.*?</div>\s*</div>)', re.DOTALL)
    match = sec6_pattern.search(content)
    
    if match:
        sec6_block = match.group(1)
        
        # Remove SECTION 6 from its original location
        content = content.replace(sec6_block, '')
        
        # Change the right column class to not stretch and be flex-column
        content = content.replace(
            '<div class="col-md-6 col-lg-5 grid-margin stretch-card">',
            '<div class="col-md-6 col-lg-5 d-flex flex-column">'
        )
        # Also need to add grid-margin to the Section 3 card if it doesn't have it, since it was on the column
        content = content.replace(
            '<!-- SECTION 3: ACTIVITY TIMELINE -->\n    <div class="col-md-6 col-lg-5 d-flex flex-column">\n      <div class="card w-100">',
            '<!-- SECTION 3: ACTIVITY TIMELINE -->\n    <div class="col-md-6 col-lg-5 d-flex flex-column">\n      <div class="card w-100 grid-margin">'
        )
        
        # Insert SECTION 6 at the end of the right column
        # Find where Section 3 card ends.
        # It's right before the closing </div> of the right column, before <!-- SECTION 4: CONTACT STATISTICS & HISTORY -->
        insertion_point = '\n  </div>\n  \n  <!-- SECTION 4: CONTACT STATISTICS & HISTORY -->'
        new_insertion = sec6_block + '\n    </div>\n  </div>\n  \n  <!-- SECTION 4: CONTACT STATISTICS & HISTORY -->'
        
        # Actually, let's just use string replacement more safely
        right_col_end = """        </div>
      </div>
    </div>
  </div>
  
  <!-- SECTION 4: CONTACT STATISTICS & HISTORY -->"""
        
        right_col_end_new = f"""        </div>
      </div>
{sec6_block}
    </div>
  </div>
  
  <!-- SECTION 4: CONTACT STATISTICS & HISTORY -->"""
        
        content = content.replace(right_col_end, right_col_end_new)
        
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f'Patched {fpath}')
    else:
        print('SECTION 6 not found')
else:
    print('File not found')
