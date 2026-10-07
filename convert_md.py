import os
for file_name in ['README.md', 'README-en.md', 'DEVELOPER_GUIDE.md']:
    if os.path.exists(file_name):
        with open(file_name, 'r', encoding='latin-1') as f:
            content = f.read()
        
        # In case the file was double encoded or something, just save as utf-8
        with open(file_name, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Converted {file_name}")
