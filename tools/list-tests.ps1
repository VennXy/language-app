Get-ChildItem tests/Feature -Name -File | Sort-Object | ForEach-Object {
    $f = "tests/Feature/$_"
    $class = (Select-String -Path $f -Pattern 'class (\w+Test)' -SimpleMatch).Matches.Groups[1].Value
    $methods = (Select-String -Path $f -Pattern 'public function test[A-Z]').Count
    Write-Host $_.PadRight(62) + "| class=$class | tests=$methods"
}
Get-ChildItem tests/Unit -Name -File | Sort-Object | ForEach-Object {
    $f = "tests/Unit/$_"
    $class = (Select-String -Path $f -Pattern 'class (\w+Test)' -SimpleMatch).Matches.Groups[1].Value
    $methods = (Select-String -Path $f -Pattern 'public function test[A-Z]').Count
    Write-Host $_.PadRight(62) + "| class=$class | tests=$methods"
}
