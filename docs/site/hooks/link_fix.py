"""MkDocs hook: rewrite sibling doc links that are outside the site nav."""

_GITHUB_DOCS = "https://github.com/cadasto/openehr-assistant-mcp/blob/main/docs"


def on_page_markdown(markdown: str, page, config, files) -> str:
    if page.file.src_path != "install.md":
        return markdown

    replacements = {
        "](development.md)": f"]({_GITHUB_DOCS}/development.md)",
    }
    for old, new in replacements.items():
        markdown = markdown.replace(old, new)
    return markdown
