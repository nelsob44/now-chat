<template>
    <div class="card card-default chat-box">
        <div class="card-header">
            <b :class="{'text-danger':sessionBlocked}">
                {{friend.name}}
                <span v-if="sessionBlocked">(Blocked)</span>
            </b>
            <a href="" @click.prevent="close">
                <i class="fa fa-times float-right" aria-hidden="true"></i>
            </a>

            <div class="dropdown float-right mr-4">
                <a href="" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fa fa-ellipsis-v float-right mr-4" aria-hidden="true"></i>
                </a>

                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" @click.prevent="unblock" v-if="sessionBlocked">Unblock</a>
                    <a class="dropdown-item" href="#" @click.prevent="block" v-else>Block</a>
                    <a class="dropdown-item" href="#" @click.prevent="clear">Clear chat</a>
                </div>
            </div>
            
            
        </div>
        <div class="card-body" v-chat-scroll>
            <p class="card-text" :class="{'text-right':chat.type == 0}" v-for="chat in chats" :key="chat.message">
                {{chat.message}}
            </p>
        </div>
        <form @submit.prevent="send" class="card-footer">
            <div class="form-group">

            </div>
            <input type="text" class="form-control" placeholder="Type your message here...." 
            :disabled="sessionBlocked" v-model="message">
        </form>
    </div>
</template>

<script>
export default {
    props:['friend'],
    data(){
        return {
            chats: [],
            sessionBlocked: false,
            message: null
        }
    },

    created(){
        this.getAllMessages();
    },
    methods:{
        send(){
            if(this.message){
                this.pushToChats(this.message);
                axios.post(`send/${this.friend.session.id}`, {
                    content: this.message,
                    to_user: this.friend.id
                });
                this.message = null;
            }
        },

        pushToChats(message) {
            this.chats.push({message: message});
        },
        close(){
            this.$emit('close');
        },
        clear(){
            this.chats = [];
        },
        block(){
            this.sessionBlocked = true;
        },
        unblock(){
            this.sessionBlocked = false;
        },

        getAllMessages(){
            axios.post(`session/${this.friend.session.id}/chats`)
            .then(res => (this.chats = res.data.data));
        }
    },
    
}
</script>

<style>
.chat-box {
    height: 400px;
}
.card-body{
    overflow-y: scroll;
}
</style>
