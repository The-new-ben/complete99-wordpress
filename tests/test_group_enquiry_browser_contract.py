import shutil
import subprocess
from pathlib import Path


def test_group_enquiry_client_contract():
    node = shutil.which('node')
    assert node, 'Node is required for the actual enquiry-client contract'
    subprocess.run([node, 'tests/group-enquiry.test.cjs'], cwd=Path(__file__).resolve().parents[1], check=True)
