# ① Google API
Gas(SpreadShhet)とPHPのデータベースをGoogle APIで連携
ローカル環境で開発
- gas_api.php
- callback.php(Googleからリダイレクトされた際に呼び出されるスクリプト)
- GAS: Rev_Gmail Data for api.gs

## ②  webhook
GasからwebhookでPHPへリクエストし、SpreadSheetとデータベースを連携
さくらサーバーで開発
- webhook_c.php
- GAS: Rev_Gmail Data（中間発表時のものを使用しているためNotion連携のスクリプトも記述されています）


  - 1. SpreadSheet. [URL https://docs.google.com/spreadsheets/d/13OpeKXCe9GXL44yIXjEFL2dLXAdXDcedeLKqqdAj9oM/edit?usp=sharing]
  - 2. GAS [URL https://script.google.com/u/0/home/projects/12NtCzxQrxZlluGzCtVm9NQxDONTMoFsQd9EYRiewpTGLOlcQEX2GXuRi/edit]
