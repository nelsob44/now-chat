<template>
<div class="container">
    <div v-show="showMainComponent">
        <div class="uploader" v-if="showMessageBox"
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
            @dragover.prevent
            @drop="onDrop"
            :class="{dragging: isDragging}">
            
            <div class="upload-control" v-show="images.length">
                <label for="file">Select a file</label>
                <button @click="upload">Upload</button>
            </div>
            
            <div v-show="!images.length">
            
                <i class="fa fa-cloud-upload"></i>
                <p>Drag your images here</p>
                <div>OR</div>
                <div class="file-input">
                    <label for="file">Select a file</label>
                    <input type="file" id="file" @change="onInputChange" multiple @click="clearImages">
                </div>
            </div>

            <div class="images-preview" v-show="images.length">
                <div class="img-wrapper" v-for="(image, index) in images" :key="index">
                    <img :src="image" :alt="`Image Uploader ${index}`">
                    <div class="details">
                        <span class="name" v-text="files[index].name"></span>
                        <span class="size" v-text="files[index].size"></span>
                    </div>
                </div>
                
            </div>
                
        </div>


        <div class="card card-default chat-box" v-if="photoAttachViewShow">
                
            <div class="card-header">
                <b :class="{'text-danger':session.block}">
                    {{friend.name}} <span v-if="isTyping"><i>is typing...</i></span>
                    <span v-if="session.block">(Blocked)</span>
                </b>
                
                <a href="" @click.prevent="close">
                    <i class="fa fa-times float-right" aria-hidden="true"></i>
                </a>

                <div class="dropdown float-right mr-4">
                    <a href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-ellipsis-v float-right mr-4" aria-hidden="true"></i>
                    </a>
                    <a href="#" @click.prevent="showCarousel">
                        <span><i>View slides</i></span>
                    </a>
                    <span class="float-right mr-4"><button class="btn btn-normal" @click="photoAttachView">Attach Picture</button></span>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="#" @click.prevent="unblock" v-if="session.block && can">Unblock</a>
                        <a class="dropdown-item" href="#" @click.prevent="block" v-if="!session.block">Block</a>
                        <a class="dropdown-item" href="#" @click.prevent="clear">Clear chat</a>
                    </div>
                </div>
                
                
            </div>
            <div class="card-body" id="scroll-target" v-scroll:#scroll-target="onScroll" v-chat-scroll>
                <div class="card-text mb-2" :class="{'text-success':chat.read_at !=null, 'text-right':chat.type == 0, 
                'background-div':chat.type == 0, 'otherbackground':chat.type == 1}" v-for="chat in reversedChats" 
                :key="chat.message_id" style="width:100%;">
                    <div v-if="chat.message" class="card-img" style="padding: 5px; text-align:left;">
                        {{chat.message}}
                    </div>
                                  
                    <a href="#" @click.prevent="showSingleImage(chat)">
                        <div v-if="chat.image !=null" class="card-img">
                            <img :src="'http://localhost/now-chat/public/images/' + chat.image" class="imageSize" v-if="chat.stringlength < 30">
                            <img :src="chat.image" class="imageSize" v-show="images.length" v-else>
                        </div>
                    </a>
                                                                      
                    <span style="font-size:8px" class="mt-1">{{chat.read_at}}</span>
                </div><br>
               
            </div> 
            <form @submit.prevent="send" class="card-footer">
                <div class="form-group">

                </div>
                <input type="text" class="form-control" placeholder="Type your message here...." 
                :disabled="session.block" v-model="message">
            </form>
        </div>
    </div>
  
</div>
</template>

<script>
export default {
    props:['friend'],
    data(){
        return {
            chats: [],
            packs: [],
            photoAttachViewShow: true,
            showMessageBox: false,
            isBaseSixtyFour: false,
            carouselImages: false,
            showMainComponent: true,
            message: null,
            isTyping: false,
            isDragging: false,
            dragCount: 0,
            files: [],
            images: [],
            pic: null,
            offsetTop: 0,
            paginations: {},                        
        }
    },

    computed: {
        session() {
            return this.friend.session;
        },

        can() {
            return this.session.blocked_by == auth.id;
        },

        reversedChats(){
            return this.chats.slice().reverse();
        }
       
    },

    watch: {
        message(value) {
           if(value){
               Echo.private(`Chat.${this.friend.session.id}`).whisper('typing', {
                    name: auth.name
                });
           }
        },
        
    },
   
    created(){
        this.read();
        this.getAllMessages();

        Echo.private(`Chat.${this.friend.session.id}`).listen(
            "PrivateChatEvent", 
            e => {
                   
                this.friend.session.open ? this.read() : "";
                this.chats.push({message: e.content, image: e.image, stringlength: e.stringlength, type: 1, sent_at: "just now", read_at: e.read_at, id: e.id});
                this.chatids = [];
                this.maxid = '';
            }
        );

        Echo.private(`Chat.${this.friend.session.id}`).listen("MsgReadEvent", e => 
            this.chats.forEach(
                chat => (chat.id == e.chat.id ? (chat.read_at = e.chat.read_at) : "")
            )
        );

        Echo.private(`Chat.${this.friend.session.id}`).listen(
            "BlockEvent", 
            e => (this.session.block = e.blocked)
        );

        Echo.private(`Chat.${this.friend.session.id}`).listenForWhisper(
            'typing', 
            (e) => {
                this.isTyping = true
                setTimeout(() => {
                    this.isTyping = false
                }, 2000);
            }
        );
    },
    methods:{
        send(){
            if(this.message){
                this.pushToChats(this.message);
                
                axios.post(`send/${this.friend.session.id}`, {
                    content: this.message,
                    to_user: this.friend.id
                }).then(res => {this.chats[this.chats.length - 1].id = res.data})
                                
                this.message = null;
                
            }
        },
        onScroll (e) {
            var scroll_window = document.getElementById('scroll-target').scrollHeight;

            this.offsetTop = e.target.scrollTop
            if(this.offsetTop == 0 && this.paginations.next_link != null){
                this.getAllMessages(this.paginations.next_link);
                
            }
            
        },

        read(){
            axios.post(`session/${this.friend.session.id}/read`);
        },

        pushToChats(message) {
            
            this.chats.push({message: this.message, image: null, stringlength:0, type: 0, 
            sent_at: "just now"});
                       
        },

        showCarousel(){
            EventBus.$emit('hideforcarousel', this.chats);
                        
        },
        
        showSingleImage(chat){
            EventBus.$emit('hide',chat);
            
            this.pic = chat.image;
        },
        
        clearImages(){
            this.images = [];
        },
        photoAttachView(){
            this.photoAttachViewShow = false;
            this.showMessageBox = true;
        },
        close(){
            this.$emit('close');
        },
        clear(){
            axios.post(`session/${this.friend.session.id}/clear`)
            .then(res => (this.chats = []));
        },
        block(){
            this.session.block = true;
            axios.post(`session/${this.friend.session.id}/block`)
            .then(res => (this.session.blocked_by = auth.id));
        },
        unblock(){
            this.session.block = false;
            axios.post(`session/${this.friend.session.id}/unblock`)
            .then(res => (this.session.blocked_by = null));
        },

        getAllMessages(page_url){
            page_url = page_url || `session/${this.friend.session.id}/chats`;
            axios.post(page_url)
            .then(res => {
                this.packs = res.data.data;
                
                this.packs.forEach(pack => {
                    this.chats.push({
                        id: pack.id, image: pack.image, message: pack.message,
                        read_at: pack.read_at, sent_at: pack.sent_at, stringlength:pack.stringlength, 
                        type: pack.type})
                })
                
                this.paginations = {
                    current_page: res.data.meta.current_page,
                    last_page: res.data.meta.last_page,
                    from_page: res.data.meta.from,
                    to_page: res.data.meta.to,
                    total_page: res.data.meta.total,
                    path_page: res.data.meta.path+"?page=",
                    first_link: res.data.links.first,
                    last_link: res.data.links.last,
                    prev_link: res.data.links.prev,
                    next_link: res.data.links.next,
                                                        
                };
                this.packs = [];
            })
           
        },


        onDragEnter(e) {
            e.preventDefault();

            this.dragCount++;
            this.isDragging = true;
        },
        onDragLeave(e) {
            e.preventDefault();

            this.dragCount--;

            if(this.dragCount <= 0)
                this.isDragging = false;
        },

        onDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            
            this.isDragging = false;

            const files = e.dataTransfer.files;

            Array.from(files).forEach(file => this.addImage(file));
        },

        addImage(file) {
            if(!file.type.match('image.*')) {
                console.log(`${file.name} is not an image`);
                return;
            }
            this.files.push(file);

            const img = new Image(),

                reader = new FileReader();

            reader.onload = (e) => this.images.push(e.target.result);

            reader.readAsDataURL(file);
            this.isBaseSixtyFour = true;
        },

        upload() {

            this.images.forEach(image => {
                this.chats.push({message: null, image: image, stringlength:100, type: 0, sent_at: "Just now"});
            });
                                                       
            axios.post(`session/${this.friend.session.id}/uploadimage`, {to_user: this.friend.id, images:this.images})
            .then(res => (this.chats[this.chats.length - 1].id = res.data));
            this.photoAttachViewShow = true;
            this.showMessageBox = false;
                              
        },
        onInputChange(e) {
            
            const files = e.target.files;
            Array.from(files).forEach(file => this.addImage(file));
        },
        
    },
    
}
</script>

<style lang="scss" scoped>
.chat-box {
    height: 400px;
}
.card-body{
    overflow-y: scroll;
}
.background-div{
    background-color: blueviolet;
    border-bottom-left-radius: 8px;
    border-top-right-radius: 8px;
    border-top-right-radius: 8px;
    
    width: auto;
    max-width: 70%;
    float:right;
}
.otherbackground{
    background-color: cornflowerblue;
    border-bottom-right-radius: 8px;
    border-top-right-radius: 8px;
    border-top-right-radius: 8px;
    
    width: auto;
    max-width: 70%;
    float:left;
}
.imageSize{
    max-width:100%;
    height:auto;
    object-fit: cover;
}


.uploader {
    width: 100%;
    background: #2196F1;
    color: #fff;
    padding: 40px 15px;
    text-align: center;
    border-radius: 10px;
    border: 3px dashed #fff;
    font-size: 20px;
    position: relative;

    &.dragging {
        background: #fff;
        color: #2196F3;
        border: 3px dashed #2196F3;

        .file-input label {
            background: #2196F3;
            color: #fff;
        }
    }

    i {
        font-size: 85px;
    }

    .file-input {
        width: 200px;
        margin: auto;
        height: 68px;
        position: relative;


        label, input {
            background: #fff;
            color: #2196F3;
            width: 100%;
            position: absolute;
            left: 0;
            top: 0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 7px;
            cursor: pointer;
        }

        input {
            opacity:0;
            z-index: -2;
        }
    }
    .images-preview {
        display: flex;
        flex-wrap: wrap;
        margin-top: 20px;

        .img-wrapper {
            width: 160px;
            display: flex;
            flex-direction: column;
            margin: 10px;
            height: 150px;
            justify-content: space-between;
            background: #fff;
            box-shadow: 5px 5px 20px #3e3737;

            img {
                max-height: 105px;
            }
        }

        .details {
            font-size: 12px;
            background: #fff;
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: self-start;
            padding: 3px 6px;

            .name {
                overflow: hidden;
                height: 18px;
            }
        }
    }

    .upload-control {
        position: absolute;
        width: 100%;
        background: #fff;
        top: 0;
        left: 0;
        border-top-left-radius: 7px;
        border-top-right-radius: 7px;
        padding: 18px;
        padding-bottom: 4px;
        text-align: right;

        button, label {
            background: #2196F3;
            border: 2px solid rgb(11, 164, 235);
            border-radius: 3px;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
        }
        label {
            padding: 2px 5px;
            margin-right: 10px;
        }
    }
}
</style>
