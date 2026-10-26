Get-ChildItem tests/Feature -Name -File | Sort-Object | ForEach-Object {
    $f = "tests/Feature/$_"
    $line = (Select-String -Path $f -Pattern 'class \w+Test').Line
    $methods = (Select-String -Path $f -Pattern 'public function test[A-Z]').Count
    Write-Host ($f.PadRight(62)) + "| " + $line + " | tests=$methods"
}  

